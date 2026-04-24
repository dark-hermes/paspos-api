<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $query = ProductCategory::query()
            ->withCount('products')
            ->latest('id');

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => ProductCategoryResource::collection($categories),
        ]);
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $category = ProductCategory::query()->create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Product category created successfully.',
            'data' => new ProductCategoryResource($category->loadCount('products')),
        ], 201);
    }

    public function show(Request $request, ProductCategory $productCategory): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        return response()->json([
            'status' => 'success',
            'data' => new ProductCategoryResource($productCategory->loadCount('products')),
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $productCategory->fill($request->validated())->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Product category updated successfully.',
            'data' => new ProductCategoryResource($productCategory->loadCount('products')),
        ]);
    }

    public function destroy(Request $request, ProductCategory $productCategory): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $productCategory->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product category deleted successfully.',
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
            'message' => 'Only admins can manage product categories.',
        ], 403);
    }
}
