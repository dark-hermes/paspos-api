<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()->with('cashier')->latest();

        if ($request->has('order_id')) {
            $query->where('order_id', $request->query('order_id'));
        }

        return response()->json([
            'status' => 'success',
            'data' => PaymentResource::collection($query->get()),
        ]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $order = Order::findOrFail($data['order_id']);

        // Check if amount exceeds remaining balance
        $totalPaid = $order->payments()->sum('amount');
        $remaining = $order->total_amount - $totalPaid;

        if ($data['amount'] > $remaining) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment amount exceeds the remaining order balance.',
            ], 422);
        }

        $data['cashier_id'] = $request->user()?->id;
        
        $payment = Payment::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment recorded successfully.',
            'data' => new PaymentResource($payment->load('cashier')),
        ], 201);
    }
}
