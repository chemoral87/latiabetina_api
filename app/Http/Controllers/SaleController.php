<?php

namespace App\Http\Controllers;

use App\Events\SaleCreated;
use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\Product;
use App\Models\Sale;
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

        if ($date = $request->get('date')) {
            $query->whereDate('sold_at', $date);
        }

        if ($request->boolean('with_items')) {
            $query->with('items.product');
        }

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'sale-index');
        $query->orderBy('created_at', 'desc');

        $sales = $query->paginate($request->get('itemsPerPage'));

        return new DataSetResource($sales);
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
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_price' => $product->price,
                'total_price' => $lineTotal,
            ];
        }

        $discount = round((float) ($data['discount'] ?? 0), 2);
        $total = round($subtotal - $discount, 2);

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
                    'status' => 'completed',
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

        // Broadcast to KDS listeners (only fires if any item requires_preparation)
        event(new SaleCreated($sale));

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
