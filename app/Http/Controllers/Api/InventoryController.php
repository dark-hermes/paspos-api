<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $query = Inventory::query()
            ->with(['store', 'product'])
            ->latest('id');

        if ($request->has('store_id')) {
            $query->where('store_id', $request->query('store_id'));
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->query('product_id'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('low_stock') && filter_var($request->query('low_stock'), FILTER_VALIDATE_BOOLEAN)) {
            $query->whereColumn('stock', '<=', 'min_stock');
        }

        $inventories = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => InventoryResource::collection($inventories),
        ]);
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();

        // Check if inventory already exists for this store-product pair
        $existing = Inventory::query()
            ->where('store_id', $data['store_id'])
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory for this store-product combination already exists.',
            ], 422);
        }

        $inventory = Inventory::query()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory created successfully.',
            'data' => new InventoryResource($inventory->load(['store', 'product'])),
        ], 201);
    }

    public function show(Request $request, Inventory $inventory): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        return response()->json([
            'status' => 'success',
            'data' => new InventoryResource($inventory->load(['store', 'product'])),
        ]);
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $inventory->fill($request->validated())->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory updated successfully.',
            'data' => new InventoryResource($inventory->load(['store', 'product'])),
        ]);
    }

    public function destroy(Request $request, Inventory $inventory): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $inventory->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory deleted successfully.',
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
            'message' => 'Only admins can manage inventories.',
        ], 403);
    }
}
