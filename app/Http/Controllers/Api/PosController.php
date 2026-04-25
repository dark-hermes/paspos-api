<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePosOrderRequest;
use App\Http\Resources\InventoryResource;
use App\Http\Resources\OrderResource;
use App\Models\Inventory;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function searchProducts(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'search' => 'required|string',
        ]);

        $search = $request->query('search');

        $inventories = Inventory::query()
            ->with(['product.category', 'product.brand'])
            ->where('store_id', $request->query('store_id'))
            ->whereHas('product', function ($q) use ($search) {
                $q->where('barcode', $search)
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('brand', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => InventoryResource::collection($inventories),
        ]);
    }

    public function placeOrder(StorePosOrderRequest $request, OrderService $orderService): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['type'] = 'pos';
            $data['cashier_id'] = $request->user()?->id;

            $order = $orderService->createOrder($data);

            return response()->json([
                'status' => 'success',
                'message' => 'POS Order created successfully.',
                'data' => new OrderResource($order),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
