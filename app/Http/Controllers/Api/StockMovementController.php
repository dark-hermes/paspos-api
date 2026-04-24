<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockMovementService $stockMovementService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $query = StockMovement::query()
            ->with(['sourceStore', 'destinationStore', 'product'])
            ->latest('id');

        if ($request->has('src_store_id')) {
            $query->where('src_store_id', $request->query('src_store_id'));
        }

        if ($request->has('dest_store_id')) {
            $query->where('dest_store_id', $request->query('dest_store_id'));
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->query('product_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%");
            });
        }

        $movements = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => StockMovementResource::collection($movements),
        ]);
    }

    public function store(StoreStockMovementRequest $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        $movement = $this->stockMovementService->create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Stock movement created successfully.',
            'data' => new StockMovementResource($movement->load(['sourceStore', 'destinationStore', 'product'])),
        ], 201);
    }

    public function show(Request $request, StockMovement $stockMovement): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        return response()->json([
            'status' => 'success',
            'data' => new StockMovementResource($stockMovement->load(['sourceStore', 'destinationStore', 'product'])),
        ]);
    }

    public function update(UpdateStockMovementRequest $request, StockMovement $stockMovement): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        // Only allow updating non-critical fields (title, note)
        $stockMovement->fill($request->only(['title', 'note']))->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Stock movement updated successfully.',
            'data' => new StockMovementResource($stockMovement->load(['sourceStore', 'destinationStore', 'product'])),
        ]);
    }

    public function destroy(Request $request, StockMovement $stockMovement): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbiddenResponse();
        }

        // Reverse inventory adjustment before deleting
        $this->stockMovementService->reverse($stockMovement);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock movement deleted and inventory reversed successfully.',
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
            'message' => 'Only admins can manage stock movements.',
        ], 403);
    }
}
