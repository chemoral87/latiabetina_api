<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\SaleCompleted;
use App\Events\SaleStatusUpdated;
use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Http\Resources\DataSetResource;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SaleController extends Controller
{
    use AppliesOrgPermissionScope;

    public function __construct(
        private readonly SaleService $saleService,
    ) {}

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

    public function daily(Request $request): JsonResponse
    {
        $query = Sale::with('items.product', 'organization');

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

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'sale-index');
        $query->orderBy('sold_at', 'asc');

        $sales = $query->get();

        return response()->json(['data' => SaleResource::collection($sales)]);
    }

    private function normalizeSaleStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        return match (strtoupper($status)) {
            'PRE', 'PREPARING' => Sale::STATUS_PREPARING,
            'COM', 'COMPLETED' => Sale::STATUS_COMPLETED,
            'PEN', 'PENDING' => Sale::STATUS_PENDING,
            'CAN', 'CANCELLED' => Sale::STATUS_CANCELLED,
            'REF', 'REFUNDED' => Sale::STATUS_REFUNDED,
            default => null,
        };
    }

    public function kds(Request $request): JsonResponse
    {
        $query = Sale::with('items.product', 'organization')
            ->whereIn('status', ['PRE', 'preparing']);

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'pos-kds');
        $query->orderBy('sold_at', 'asc');

        $sales = $query->get();

        return response()->json(['data' => SaleResource::collection($sales)]);
    }

    public function show(Sale $sale): SaleResource
    {
        return new SaleResource($sale->load('items.product'));
    }

    public function update(UpdateSaleRequest $request, Sale $sale): JsonResponse
    {
        $data = $request->validated();

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
                        $deduct = min($quantityDiff, $product->stock);
                        if ($deduct > 0) {
                            $product->decrement('stock', $deduct);
                        }
                    } else {
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

        $sale->items()->whereNotIn('id', $updatedItemIds)->each(function ($removedItem) {
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
            'data' => new SaleResource($sale->fresh()->load('items.product')),
        ]);
    }

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

        event(new SaleCompleted($sale));

        return response()->json([
            'success' => __('messa.sale_update', ['number' => $sale->number]),
            'data' => new SaleResource($sale->fresh()->load('items.product')),
        ]);
    }

    public function updateItem(Request $request, Sale $sale, SaleItem $saleItem): JsonResponse
    {
        if ($saleItem->sale_id !== $sale->id) {
            return response()->json(['error' => 'Item does not belong to this sale'], 422);
        }

        $status = strtoupper($request->get('status'));
        $status = match ($status) {
            'PEN', 'PENDING' => SaleItem::PREPARATION_PENDING,
            'REA', 'READY' => SaleItem::PREPARATION_READY,
            'COM', 'COMPLETED' => SaleItem::PREPARATION_COMPLETED,
            default => null,
        };

        if (!in_array($status, [SaleItem::PREPARATION_PENDING, SaleItem::PREPARATION_READY, SaleItem::PREPARATION_COMPLETED])) {
            return response()->json(['error' => 'Invalid status'], 422);
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
        } elseif ($pendingItems > 0 && $sale->status === Sale::STATUS_COMPLETED) {
            $sale->status = Sale::STATUS_PREPARING;
            $sale->save();
            event(new SaleStatusUpdated($sale));
        }

        return response()->json([
            'success' => __('messa.sale_update', ['number' => $sale->number]),
            'data' => new SaleResource($saleItem->fresh()->load('product')),
        ]);
    }

    public function destroy(Sale $sale): JsonResponse
    {
        foreach ($sale->items as $item) {
            Product::where('id', $item->product_id)->increment('stock', $item->quantity);
        }

        $sale->items()->delete();
        $sale->delete();

        return response()->json([
            'success' => __('messa.sale_deleted', ['number' => $sale->number]),
        ]);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $sale = $this->saleService->createSale($data, $request->user()->id);

        return response()->json([
            'success' => __('messa.sale_create', ['number' => $sale->number]),
            'data' => new SaleResource($sale->load('items.product')),
        ], 201);
    }
}
