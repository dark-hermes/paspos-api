<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderShippingRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderManagementController extends Controller
{
    public function updateShipping(UpdateOrderShippingRequest $request, Order $order): JsonResponse
    {
        $this->authorize('manage', $order);

        if ($order->type !== 'online') {
            return $this->unprocessableResponse('Shipping update is only available for online orders.');
        }

        $data = $request->validated();

        try {
            $order = DB::transaction(function () use ($order, $data): Order {
                $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

                if (in_array($lockedOrder->status, ['cancelled', 'completed'], true)) {
                    throw new RuntimeException('Cannot update shipping for this order status.');
                }

                $shippingFee = (float) $data['shipping_fee'];
                $subtotal = (float) $lockedOrder->items()->sum('subtotal');

                $lockedOrder->shipping_fee = $shippingFee;
                $lockedOrder->courier_name = $data['courier_name'] ?? null;
                $lockedOrder->total_amount = $subtotal + $shippingFee;
                $lockedOrder->status = 'processing';
                $lockedOrder->save();

                return $lockedOrder;
            });
        } catch (\Throwable $exception) {
            return $this->unprocessableResponse($exception->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Shipping details updated successfully.',
            'data' => new OrderResource($order->load(['store', 'customer', 'cashier', 'items.product', 'payments'])),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('manage', $order);

        if ($order->type !== 'online') {
            return $this->unprocessableResponse('Status update is only available for online orders.');
        }

        $data = $request->validated();

        try {
            $order = DB::transaction(function () use ($order, $data): Order {
                $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($lockedOrder->status !== 'processing') {
                    throw new RuntimeException('Order must be in processing status before it can be shipped.');
                }

                $lockedOrder->status = $data['status'];
                $lockedOrder->save();

                return $lockedOrder;
            });
        } catch (\Throwable $exception) {
            return $this->unprocessableResponse($exception->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully.',
            'data' => new OrderResource($order->load(['store', 'customer', 'cashier', 'items.product', 'payments'])),
        ]);
    }

    public function completeCod(Request $request, Order $order): JsonResponse
    {
        $this->authorize('manage', $order);

        if ($order->type !== 'online' || $order->payment_method !== 'cod') {
            return $this->unprocessableResponse('COD completion is only available for online COD orders.');
        }

        try {
            $order = DB::transaction(function () use ($request, $order): Order {
                $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($lockedOrder->status !== 'shipped') {
                    throw new RuntimeException('Order must be shipped before COD completion.');
                }

                if ($lockedOrder->payment_status !== 'unpaid') {
                    throw new RuntimeException('Order COD completion has already been processed.');
                }

                Payment::create([
                    'order_id' => $lockedOrder->id,
                    'cashier_id' => $request->user()?->id,
                    'amount' => $lockedOrder->total_amount,
                    'payment_method' => 'cash',
                    'note' => 'COD completion',
                ]);

                $lockedOrder->refresh();
                $lockedOrder->status = 'completed';
                $lockedOrder->save();

                return $lockedOrder;
            });
        } catch (\Throwable $exception) {
            return $this->unprocessableResponse($exception->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'COD order completed successfully.',
            'data' => new OrderResource($order->load(['store', 'customer', 'cashier', 'items.product', 'payments'])),
        ]);
    }

    private function unprocessableResponse(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], 422);
    }
}
