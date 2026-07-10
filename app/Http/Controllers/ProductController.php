<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use AppliesOrgPermissionScope;

    protected string $path = '/pos-products/';

    public function index(Request $request): DataSetResource
    {
        $query = queryServerSide($request, Product::query());

        if ($filter = $request->get('filter')) {
            $query->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('sku', 'like', "%{$filter}%");
            });
        }

        if ($orgId = $request->get('org_id')) {
            $query->where('org_id', $orgId);
        }

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'product-index');
        if (!$request->has('sortBy')) {
            $query->orderBy('name', 'asc');
        }

        $products = $query->paginate($request->get('itemsPerPage'));

        return new DataSetResource($products);
    }

    public function show(Product $product): Product
    {
        return $product;
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'org_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:pos_products,sku',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'hidden' => 'boolean',
            'requires_preparation' => 'boolean',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'order' => 'nullable|integer',
        ]);

        if ($request->filled('image') && str_starts_with($request->image, 'data:')) {
            $path = "ORG-{$data['org_id']}{$this->path}";
            $treatedImage = treatImage($request->image, 80);
            $data['image'] = saveS3Blob($treatedImage, $path);
        }

        if (empty($data['sku'])) {
            $data['sku'] = Str::upper(Str::slug($data['name'])) . '-' . Str::random(4);
        }

        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $product = Product::create($data);

        return response()->json([
            'success' => 'Producto creado',
            'data' => $product,
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'org_id' => 'sometimes|exists:organizations,id',
            'name' => 'sometimes|string|max:255',
            'sku' => 'nullable|string|max:100|unique:pos_products,sku,' . $product->id,
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'hidden' => 'boolean',
            'requires_preparation' => 'boolean',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'order' => 'nullable|integer',
        ]);

        if ($request->filled('image') && str_starts_with($request->image, 'data:')) {
            $orgId = $data['org_id'] ?? $product->org_id;
            $path = "ORG-{$orgId}{$this->path}";
            $treatedImage = treatImage($request->image, 80);
            $data['image'] = saveS3Blob($treatedImage, $path, $product->image);
        }

        if (empty($data['sku']) && $request->has('name')) {
            $data['sku'] = Str::upper(Str::slug($data['name'])) . '-' . Str::random(4);
        }

        $data['updated_by'] = $request->user()->id;
        $product->update($data);

        return response()->json([
            'success' => 'Producto actualizado',
            'data' => $product,
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['success' => 'Producto eliminado']);
    }
}
