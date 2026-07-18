<?php

namespace App\Http\Controllers;

use App\Events\SaleCreated;
use App\Events\SaleCompleted;
use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    use AppliesOrgPermissionScope;
    public function index(Request $request): DataSetResource
    {
        $query = queryServerSide($request, Sale::query());

        if ($filter = $request->get('filter')) {
            $query->where(function ($q) use ($filter) {
                $q->where('number', 'like', "%{$filter}%")
                    ->orWhere('customer_name', 'like', "%{$filter}%");
            });
        }

        if ($orgId = $request->get('org_id')) {
            $query->where('org_id', $orgId);
        }

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'sale-index');
        $query->orderBy('created_at', 'desc');

        $sales = $query->paginate($request->get('itemsPerPage'));

        return new DataSetResource($sales);
    }

    /**
     * Returns sales with items and products eagerly loaded.
     * Intended for the cash-close / daily report view.
     *
     * GET /sale/daily?date=2026-07-17&org_id=1&status=preparing
     * - date    filters by sold_at date (defaults to today); ignored when status is provided
     * - org_id  optional org filter
     * - status  optional status filter (e.g. 'preparing'); when present, date filter is skipped
     */
    public function daily(Request $request): JsonResponse
    {
        $query = Sale::with('items.product', 'organization');

        // When a status filter is given (e.g. KDS fetching all orders)
        // accept both legacy names and new uppercase codes.
        if ($status = $request->get('status')) {
            $normalized = $this->normalizeSaleStatus($status);
            if ($normalized) {
                $query->whereIn('status', array_unique(array_filter([$normalized, $status, strtolower($status)])));
            } else {
                $query->where('status', $status);
            }
        } else {
            $date = $request->get('date', now()->toDateString());
            $query->whereDate('sold_at', $date);
        }

        // if ($orgId = $request->get('org_id')) {
        //     $query->where('org_id', $orgId);
        // }

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'sale-index');
        $query->orderBy('sold_at', 'asc');

        $sales = $query->get();

        return response()->json(['data' => $sales]);
    }

    private function normalizeSaleStatus(?string $status): ?string
    {
        if (! $status) {
            return null;
        }

        return match (strtoupper($status)) {
            'PRE', 'PREPARING' => Sale::STATUS_PREPARING,
            'COM', 'COMPLETED' => Sale::STATUS_COMPLETED,
            'PEN', 'PENDING' => Sale::STATUS_PENDING,
            'CAN', 'CANCELLED' => Sale::STATUS_CANCELLED,
            'REF', 'REFUNDED'  => Sale::STATUS_REFUNDED,
            default => null,
        };
    }

    /**
     * Returns all orders currently in 'preparing' status for the Kitchen Display System (KDS).
     * Requires 'pos-kds' permission (separate from 'sale-index').
     * No date filtering — fetches all orders regardless of date where status = 'preparing'.
     * Source of truth is the database status column.
     *
     * GET /sale/kds
     */
    public function kds(Request $request): JsonResponse
    {
        $query = Sale::with('items.product', 'organization')
            ->whereIn('status', ['PRE', 'preparing']);

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'pos-kds');
        $query->orderBy('sold_at', 'asc');

        $sales = $query->get();

        return response()->json(['data' => $sales]);
    }

    public function show(Sale $sale): Sale
    {
        return $sale->load('items.product');
    }

    public function update(Request $request, Sale $sale): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:pos_sale_items,id',
            'items.*.product_id' => 'required|exists:pos_products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Update customer info
        $sale->customer_name = $data['customer_name'] ?? $sale->customer_name;
        $sale->customer_phone = $data['customer_phone'] ?? $sale->customer_phone;

        $subtotal = 0;
        $updatedItemIds = [];

        foreach ($data['items'] as $itemData) {
            $existingItem = $sale->items()->where('product_id', $itemData['product_id'])->first();

            if ($existingItem) {
                $oldQuantity = $existingItem->quantity;
                $newQuantity = (int) $itemData['quantity'];
                $quantityDiff = $newQuantity - $oldQuantity;

                if ($quantityDiff !== 0) {
                    $product = Product::findOrFail($itemData['product_id']);

                    if ($quantityDiff > 0) {
                        // Taking more from stock — deduct only what's available, floor at 0
                        $deduct = min($quantityDiff, $product->stock);
                        if ($deduct > 0) {
                            $product->decrement('stock', $deduct);
                        }
                    } else {
                        // Returning items to stock
                        $product->increment('stock', abs($quantityDiff));
                    }
                }

                $lineTotal = round($existingItem->unit_price * $newQuantity, 2);
                $existingItem->update([
                    'quantity' => $newQuantity,
                    'total_price' => $lineTotal,
                ]);

                $updatedItemIds[] = $existingItem->id;
                $subtotal += $lineTotal;
            } else {
                // New item added to the sale
                $product = Product::where('id', $itemData['product_id'])
                    ->where('org_id', $sale->org_id)
                    ->firstOrFail();

                $lineTotal = round($product->price * $itemData['quantity'], 2);
                $newItem = $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $lineTotal,
                ]);

                $updatedItemIds[] = $newItem->id;
                $deduct = min($itemData['quantity'], $product->stock);
                if ($deduct > 0) {
                    $product->decrement('stock', $deduct);
                }
                $subtotal += $lineTotal;
            }
        }

        $updatedItemIds = array_filter($updatedItemIds);

        // Remove items that are no longer in the sale
        $sale->items()->whereNotIn('id', $updatedItemIds)->each(function ($removedItem) {
            // Restore stock for removed items
            Product::where('id', $removedItem->product_id)->increment('stock', $removedItem->quantity);
            $removedItem->delete();
        });

        $discount = round((float) ($sale->discount ?? 0), 2);
        $total = round($subtotal - $discount, 2);

        $sale->subtotal = $subtotal;
        $sale->total = $total;
        $sale->save();

        return response()->json([
            'success' => __('messa.sale_update', ['number' => $sale->number]),
            'data' => $sale->fresh()->load('items.product'),
        ]);
    }

    /**
     * Mark a sale as completed from the KDS.
     * Also marks all pending preparation items as ready.
     * PATCH /sale/{sale}/complete
     */
    public function complete(Sale $sale): JsonResponse
    {
        $sale->items()
            ->where('preparation_status', SaleItem::PREPARATION_PENDING)
            ->update([
                'preparation_status' => SaleItem::PREPARATION_COMPLETED,
                'completed_quantity' => DB::raw('quantity'),
            ]);

        $sale->status = Sale::STATUS_COMPLETED;
        $sale->save();

        // Broadcast to real-time listeners on the sales channel
        event(new SaleCompleted($sale));

        return response()->json([
            'success' => __('messa.sale_update', ['number' => $sale->number]),
            'data'    => $sale->fresh()->load('items.product'),
        ]);
    }

    /**
     * Update individual sale item status (pending, ready or completed).
     * PATCH /sale/{sale}/item/{saleItem}
     * Body: { "status": "pending" | "ready" | "completed" }
     */
    public function updateItem(Request $request, Sale $sale, SaleItem $saleItem): JsonResponse
    {
        // Verify the item belongs to this sale
        if ($saleItem->sale_id !== $sale->id) {
            return response()->json([
                'error' => 'Item does not belong to this sale',
            ], 422);
        }

        $status = strtoupper($request->get('status'));
        $status = match ($status) {
            'PEN', 'PENDING' => SaleItem::PREPARATION_PENDING,
            'REA', 'READY' => SaleItem::PREPARATION_READY,
            'COM', 'COMPLETED' => SaleItem::PREPARATION_COMPLETED,
            default => null,
        };

        if (!in_array($status, [SaleItem::PREPARATION_PENDING, SaleItem::PREPARATION_READY, SaleItem::PREPARATION_COMPLETED])) {
            return response()->json([
                'error' => 'Invalid status. Must be "pending", "ready", "completed", "PEN", "REA" or "COM"',
            ], 422);
        }

        if ($status === SaleItem::PREPARATION_COMPLETED) {
            $newCompletedQuantity = min($saleItem->completed_quantity + 1, $saleItem->quantity);
            $saleItem->update([
                'completed_quantity' => $newCompletedQuantity,
                'preparation_status' => $newCompletedQuantity === $saleItem->quantity
                    ? SaleItem::PREPARATION_COMPLETED
                    : SaleItem::PREPARATION_READY,
            ]);
        } elseif ($status === SaleItem::PREPARATION_READY) {
            $saleItem->update([
                'preparation_status' => SaleItem::PREPARATION_READY,
                'completed_quantity' => $saleItem->quantity,
            ]);
        } else {
            $newCompletedQuantity = max(0, $saleItem->completed_quantity - 1);
            $saleItem->update([
                'completed_quantity' => $newCompletedQuantity,
                'preparation_status' => $newCompletedQuantity === 0
                    ? SaleItem::PREPARATION_PENDING
                    : SaleItem::PREPARATION_READY,
            ]);
        }

        $pendingItems = $sale->items()
            ->whereNotNull('preparation_status')
            ->where('preparation_status', '!=', SaleItem::PREPARATION_COMPLETED)
            ->count();

        if ($pendingItems === 0 && $sale->status !== Sale::STATUS_COMPLETED) {
            $sale->status = Sale::STATUS_COMPLETED;
            $sale->save();
            event(new SaleCompleted($sale));
        }

        return response()->json([
            'success' => __('messa.sale_update', ['number' => $sale->number]),
            'data'    => $saleItem->fresh()->load('product'),
        ]);
    }

    public function destroy(Sale $sale): JsonResponse
    {
        // Restore product stock before deleting items
        foreach ($sale->items as $item) {
            Product::where('id', $item->product_id)->increment('stock', $item->quantity);
        }

        $sale->items()->delete();
        $sale->delete();

        return response()->json([
            'success' => __('messa.sale_deleted', ['number' => $sale->number]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'org_id' => 'required|exists:organizations,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'payment_method' => 'nullable|string|max:50',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:pos_products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $subtotal = 0;
        $saleItems = [];

        foreach ($data['items'] as $item) {
            $product = Product::where('id', $item['product_id'])->where('org_id', $data['org_id'])->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => [__('messa.sale_item_invalid_org')],
                ]);
            }

            $lineTotal = round($product->price * $item['quantity'], 2);
            $subtotal += $lineTotal;

            $saleItems[] = [
                'product_id'         => $product->id,
                'quantity'           => $item['quantity'],
                'unit_price'         => $product->price,
                'total_price'        => $lineTotal,
                'preparation_status' => $product->requires_preparation ? SaleItem::PREPARATION_PENDING : null,
            ];
        }

        $discount = round((float) ($data['discount'] ?? 0), 2);
        $total = round($subtotal - $discount, 2);

        // Determine up-front whether any item needs kitchen preparation
        // so we can set the correct initial status on the sale record.
        $requiresPreparation = collect($saleItems)->contains(function ($item) {
            $product = Product::find($item['product_id']);
            return $product?->requires_preparation === true;
        });

        // Generate number per org: RAND4-XX (01-99), rotating to a new random
        // 4-character code once a prefix's sequence reaches 99.
        $sale = null;
        $attempts = 0;

        while (! $sale) {
            $number = $this->generateSaleNumber($data['org_id']);

            try {
                $sale = Sale::create([
                    'number' => $number,
                    'org_id' => $data['org_id'],
                    'customer_name' => $data['customer_name'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'status' => $requiresPreparation ? Sale::STATUS_PREPARING : Sale::STATUS_COMPLETED,
                    'created_by' => $request->user()->id,
                    'sold_at' => now(),
                ]);
            } catch (QueryException $e) {
                $isDuplicate = (int) ($e->errorInfo[1] ?? 0) === 1062;

                if (! $isDuplicate || ++$attempts >= 5) {
                    throw $e;
                }
                // Duplicate number (rare race condition): loop and try again
                // with a freshly generated number.
            }
        }

        foreach ($saleItems as $item) {
            $sale->items()->create($item);

            $product = Product::find($item['product_id']);
            if ($product) {
                $deduct = min($item['quantity'], $product->stock);
                if ($deduct > 0) {
                    $product->decrement('stock', $deduct);
                }
            }
        }

        // Broadcast to KDS listeners — only when at least one item requires preparation
        if ($requiresPreparation) {
            event(new SaleCreated($sale));
        }

        return response()->json([
            'success' => __('messa.sale_create', ['number' => $sale->number]),
            'data' => $sale->load('items.product'),
        ], 201);
    }

    /**
     * Generate the next sale number for an organization: PREFIX-XX.
     * PREFIX is a random 4-character uppercase alphanumeric code that stays
     * fixed while the sequence (01-99) climbs. Once the sequence for the
     * current prefix reaches 99, a new random prefix is generated and the
     * sequence resets to 01. Locks the org's last sale row so concurrent
     * requests for the same org can't compute the same number.
     */
    private function generateSaleNumber(int $orgId): string
    {
        return DB::transaction(function () use ($orgId) {
            $lastSale = Sale::where('org_id', $orgId)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastSale && preg_match('/^([A-Z0-9]{4})-(\d{2})$/', $lastSale->number, $matches)) {
                $prefix = $matches[1];
                $lastSeq = (int) $matches[2];

                if ($lastSeq < 99) {
                    $nextSeq = $lastSeq + 1;
                } else {
                    $prefix = $this->generateUniquePrefix($orgId);
                    $nextSeq = 1;
                }
            } else {
                $prefix = $this->generateUniquePrefix($orgId);
                $nextSeq = 1;
            }

            return $prefix . '-' . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Generate a random 4-character uppercase alphanumeric code that is not
     * already in use as a prefix for this organization's sales.
     */
    private function generateUniquePrefix(int $orgId): string
    {
        do {
            $prefix = $this->generateRandomCode(4);

            $exists = Sale::where('org_id', $orgId)
                ->where('number', 'like', $prefix . '-%')
                ->exists();
        } while ($exists);

        return $prefix;
    }

    private function generateRandomCode(int $length = 4): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $code;
    }
}
