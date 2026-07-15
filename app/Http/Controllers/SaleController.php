<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function show(Sale $sale): Sale
    {
        return $sale->load('items.product');
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

            if ($product->stock < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => [__('messa.sale_item_insufficient_stock', ['name' => $product->name])],
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
        $number = 'POS-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));

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

        foreach ($saleItems as $item) {
            $sale->items()->create($item);

            $product = Product::find($item['product_id']);
            if ($product) {
                $product->decrement('stock', $item['quantity']);
            }
        }

        return response()->json([
            'success' => __('messa.sale_create', ['number' => $sale->number]),
            'data' => $sale->load('items.product'),
        ], 201);
    }
}
