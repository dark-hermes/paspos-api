<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Order::query()
            ->with(['store', 'customer', 'cashier'])
            ->latest();

        // Granular Authorization: Branch Admin & Cashier can only see their store's orders
        if (in_array($user->role, ['branch_admin', 'cashier'], true)) {
            $query->where('store_id', $user->store_id);
        }

        if ($request->has('store_id') && $user->role === 'main_admin') {
            $query->where('store_id', $request->query('store_id'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->query('payment_status'));
        }

        return response()->json([
            'status' => 'success',
            'data' => OrderResource::collection($query->paginate(15)),
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'status' => 'success',
            'data' => new OrderResource($order->load(['store', 'customer', 'cashier', 'items.product', 'payments.cashier'])),
        ]);
    }
}
