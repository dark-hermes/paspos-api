<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $query = Product::query()
            ->with(['category', 'brand'])
            ->latest('id');

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->query('brand_id'));
        }

        $products = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => ProductResource::collection($products),
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        unset($data['image']);

        $product = Product::query()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product->load(['category', 'brand'])),
        ], 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        return response()->json([
            'status' => 'success',
            'data' => new ProductResource($product->load(['category', 'brand'])),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        unset($data['image']);

        $product->fill($data)->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product->load(['category', 'brand'])),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        // Delete image file if exists
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully.',
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        return in_array($request->user()?->role, ['main_admin', 'branch_admin']);
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Only admins can manage products.',
        ], 403);
    }
}
