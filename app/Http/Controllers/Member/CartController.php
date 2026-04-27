<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutCartRequest;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Resources\OrderResource;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->isMember($request)) {
            return $this->forbiddenResponse();
        }

        $cartItems = CartItem::query()
            ->where('user_id', $request->user()->id)
            ->with(['store', 'product.brand', 'product.category'])
            ->latest('id')
            ->get();

        $inventoryMap = $this->inventoryMapForCartItems($cartItems);

        return response()->json([
            'status' => 'success',
            'data' => $cartItems
                ->map(fn(CartItem $cartItem): array => $this->cartItemPayload($cartItem, $inventoryMap))
                ->values(),
        ]);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        if (! $this->isMember($request)) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();

        $inventory = Inventory::query()
            ->where('store_id', $data['store_id'])
            ->where('product_id', $data['product_id'])
            ->first();

        if (! $inventory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Selected product is not available in the selected store inventory.',
            ], 422);
        }

        $cartItem = CartItem::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'store_id' => $data['store_id'],
            'product_id' => $data['product_id'],
        ]);

        $isNew = ! $cartItem->exists;
        $cartItem->quantity = ($cartItem->exists ? (int) $cartItem->quantity : 0) + (int) $data['quantity'];
        $cartItem->save();

        $cartItem->load(['store', 'product.brand', 'product.category']);
        $inventoryMap = collect([
            $this->inventoryMapKey((int) $inventory->store_id, (int) $inventory->product_id) => $inventory,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $isNew ? 'Cart item added successfully.' : 'Cart item quantity updated successfully.',
            'data' => $this->cartItemPayload($cartItem, $inventoryMap),
        ], $isNew ? 201 : 200);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        if (! $this->isMember($request)) {
            return $this->forbiddenResponse();
        }

        if ($cartItem->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not found.',
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cart item deleted successfully.',
        ]);
    }

    public function checkout(CheckoutCartRequest $request, OrderService $orderService): JsonResponse
    {
        if (! $this->isMember($request)) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();
        $storeId = (int) $data['store_id'];

        $cartItems = CartItem::query()
            ->where('user_id', $request->user()->id)
            ->where('store_id', $storeId)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart is empty for the selected store.',
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($request, $data, $storeId, $cartItems, $orderService) {
                $order = $orderService->createOrder([
                    'type' => 'online',
                    'store_id' => $storeId,
                    'customer_id' => $request->user()->id,
                    'payment_method' => $data['payment_method'] ?? 'cod',
                    'shipping_name' => $data['shipping_name'],
                    'shipping_receiver_name' => $data['shipping_receiver_name'],
                    'shipping_receiver_phone' => $data['shipping_receiver_phone'],
                    'shipping_address' => $data['shipping_address'],
                    'shipping_notes' => $data['shipping_notes'] ?? null,
                    'shipping_fee' => 0,
                    'items' => $cartItems->map(fn(CartItem $cartItem): array => [
                        'product_id' => $cartItem->product_id,
                        'quantity' => $cartItem->quantity,
                    ])->values()->all(),
                ]);

                CartItem::query()->whereIn('id', $cartItems->pluck('id'))->delete();

                return $order;
            });
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Checkout completed successfully.',
            'data' => new OrderResource($order->load(['store', 'customer', 'cashier', 'items.product', 'payments'])),
        ], 201);
    }

    private function inventoryMapForCartItems(Collection $cartItems): Collection
    {
        if ($cartItems->isEmpty()) {
            return collect();
        }

        return Inventory::query()
            ->whereIn('store_id', $cartItems->pluck('store_id')->unique()->values())
            ->whereIn('product_id', $cartItems->pluck('product_id')->unique()->values())
            ->get()
            ->keyBy(fn(Inventory $inventory): string => $this->inventoryMapKey((int) $inventory->store_id, (int) $inventory->product_id));
    }

    private function cartItemPayload(CartItem $cartItem, Collection $inventoryMap): array
    {
        $inventory = $inventoryMap->get($this->inventoryMapKey((int) $cartItem->store_id, (int) $cartItem->product_id));
        $currentUnitPrice = $inventory ? $this->calculateCurrentUnitPrice($inventory) : null;

        return [
            'id' => $cartItem->id,
            'user_id' => $cartItem->user_id,
            'store_id' => $cartItem->store_id,
            'product_id' => $cartItem->product_id,
            'quantity' => (int) $cartItem->quantity,
            'current_stock' => $inventory ? (float) $inventory->stock : null,
            'current_unit_price' => $currentUnitPrice,
            'is_active' => $inventory ? (bool) $inventory->is_active : false,
            'line_subtotal' => $currentUnitPrice !== null ? round($currentUnitPrice * (int) $cartItem->quantity, 2) : null,
            'store' => $cartItem->store ? [
                'id' => $cartItem->store->id,
                'name' => $cartItem->store->name,
                'type' => $cartItem->store->type,
            ] : null,
            'product' => $cartItem->product ? [
                'id' => $cartItem->product->id,
                'name' => $cartItem->product->name,
                'sku' => $cartItem->product->sku,
                'barcode' => $cartItem->product->barcode,
                'unit' => $cartItem->product->unit,
                'weight' => $cartItem->product->weight !== null ? (float) $cartItem->product->weight : null,
            ] : null,
            'created_at' => $cartItem->created_at?->toIso8601String(),
            'updated_at' => $cartItem->updated_at?->toIso8601String(),
        ];
    }

    private function inventoryMapKey(int $storeId, int $productId): string
    {
        return $storeId . ':' . $productId;
    }

    private function calculateCurrentUnitPrice(Inventory $inventory): float
    {
        $unitPrice = (float) $inventory->selling_price;
        $discountPercentage = (int) $inventory->discount_percentage;

        if ($discountPercentage > 0) {
            $unitPrice -= $unitPrice * ($discountPercentage / 100);
        }

        return round($unitPrice, 2);
    }

    private function isMember(Request $request): bool
    {
        return $request->user()?->role === 'member';
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Only members can manage carts.',
        ], 403);
    }
}
