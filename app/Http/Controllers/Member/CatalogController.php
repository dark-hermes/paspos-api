<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberCatalogRequest;
use App\Http\Resources\Member\MemberBrandResource;
use App\Http\Resources\Member\MemberProductCategoryResource;
use App\Http\Resources\Member\MemberProductResource;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function products(MemberCatalogRequest $request, int $branch): JsonResponse
    {
        $request->validateBranch($branch);
        $search = $request->validated('search');

        $products = Product::query()
            ->with([
                'category',
                'brand',
                'inventories' => function ($query) use ($branch): void {
                    $query->where('store_id', $branch)
                        ->where('is_active', true);
                },
            ])
            ->whereHas('inventories', function (Builder $query) use ($branch): void {
                $query->where('store_id', $branch)
                    ->where('is_active', true);
            })
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhereHas('brand', function (Builder $brandQuery) use ($search): void {
                            $brandQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('category', function (Builder $categoryQuery) use ($search): void {
                            $categoryQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => MemberProductResource::collection($products),
        ]);
    }

    public function show(MemberCatalogRequest $request, int $branch, Product $product): JsonResponse
    {
        $request->validateBranch($branch);

        $product = Product::query()
            ->with([
                'category',
                'brand',
                'inventories' => function ($query) use ($branch): void {
                    $query->where('store_id', $branch)
                        ->where('is_active', true);
                },
            ])
            ->whereKey($product->id)
            ->whereHas('inventories', function (Builder $query) use ($branch): void {
                $query->where('store_id', $branch)
                    ->where('is_active', true);
            })
            ->first();

        if (! $product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found in the selected branch.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new MemberProductResource($product),
        ]);
    }

    public function categories(MemberCatalogRequest $request, int $branch): JsonResponse
    {
        $request->validateBranch($branch);
        $search = $request->validated('search');

        $categories = ProductCategory::query()
            ->whereHas('products.inventories', function (Builder $query) use ($branch): void {
                $query->where('store_id', $branch)
                    ->where('is_active', true);
            })
            ->when($search, function (Builder $query, string $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->withCount([
                'products' => function (Builder $query) use ($branch): void {
                    $query->whereHas('inventories', function (Builder $inventoryQuery) use ($branch): void {
                        $inventoryQuery->where('store_id', $branch)
                            ->where('is_active', true);
                    });
                },
            ])
            ->latest('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => MemberProductCategoryResource::collection($categories),
        ]);
    }

    public function brands(MemberCatalogRequest $request, int $branch): JsonResponse
    {
        $request->validateBranch($branch);
        $search = $request->validated('search');

        $brands = Brand::query()
            ->whereHas('products.inventories', function (Builder $query) use ($branch): void {
                $query->where('store_id', $branch)
                    ->where('is_active', true);
            })
            ->when($search, function (Builder $query, string $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->withCount([
                'products' => function (Builder $query) use ($branch): void {
                    $query->whereHas('inventories', function (Builder $inventoryQuery) use ($branch): void {
                        $inventoryQuery->where('store_id', $branch)
                            ->where('is_active', true);
                    });
                },
            ])
            ->latest('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => MemberBrandResource::collection($brands),
        ]);
    }
}
