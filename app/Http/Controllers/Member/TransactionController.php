<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\Member\MemberTransactionResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->where('customer_id', $request->user()->id)
            ->with(['store', 'items.product', 'payments'])
            ->latest();

        // Optionally filter by branch (store_id)
        if ($request->has('branch')) {
            $query->where('store_id', $request->query('branch'));
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->query('payment_status'));
        }

        // Filter by order type (pos or online)
        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        // Filter by status (completed, pending, etc.)
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'status' => 'success',
            'data' => MemberTransactionResource::collection($query->paginate(15)),
        ]);
    }

    public function show(Request $request, int $transaction): JsonResponse
    {
        $order = Order::findOrFail($transaction);

        // Ensure member can only view their own transactions
        if ($order->customer_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to view this transaction.',
            ], 403);
        }

        $order->load(['store', 'items.product', 'payments']);

        return response()->json([
            'status' => 'success',
            'data' => new MemberTransactionResource($order),
        ]);
    }
}
