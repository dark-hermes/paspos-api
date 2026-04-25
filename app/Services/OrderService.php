<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    /**
     * @param array $data Validated data containing: type, store_id, customer_id, cashier_id, payment_method, items, shipping_* fields.
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Double validation for pay_later
            if ($data['payment_method'] === 'pay_later') {
                if (($data['type'] ?? 'pos') !== 'pos' || empty($data['customer_id'])) {
                    throw new Exception('Pay later is only allowed for POS transactions with a registered member.');
                }
            }

            $storeId = $data['store_id'];
            $itemsData = $data['items']; // array of ['product_id' => x, 'quantity' => y]

            $totalAmount = 0;
            $orderItemsToCreate = [];
            $inventoryUpdates = [];

            foreach ($itemsData as $item) {
                // Pull strictly from inventory for locking and safe pricing
                $inventory = Inventory::query()
                    ->where('store_id', $storeId)
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$inventory || $inventory->stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for product ID {$item['product_id']}.");
                }

                $quantity = $item['quantity'];
                $baseCost = $inventory->purchase_price;
                $unitPrice = $inventory->selling_price;
                
                // If there's a discount_percentage in inventory, apply it
                if ($inventory->discount_percentage > 0) {
                    $discount = $unitPrice * ($inventory->discount_percentage / 100);
                    $unitPrice = $unitPrice - $discount;
                }

                $subtotal = $unitPrice * $quantity;
                $totalAmount += $subtotal;

                $orderItemsToCreate[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'base_cost' => $baseCost,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];

                $inventoryUpdates[] = [
                    'inventory' => $inventory,
                    'quantity' => $quantity
                ];
            }

            // Create Order
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'type' => $data['type'] ?? 'pos',
                'store_id' => $storeId,
                'customer_id' => $data['customer_id'] ?? null,
                'cashier_id' => $data['cashier_id'] ?? null,
                'total_amount' => $totalAmount,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
                'status' => ($data['type'] ?? 'pos') === 'pos' ? 'completed' : 'pending',
                'shipping_name' => $data['shipping_name'] ?? null,
                'shipping_receiver_name' => $data['shipping_receiver_name'] ?? null,
                'shipping_receiver_phone' => $data['shipping_receiver_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_notes' => $data['shipping_notes'] ?? null,
            ]);

            // Create Order Items
            $order->items()->createMany($orderItemsToCreate);

            // Deduct stock and log movements
            foreach ($inventoryUpdates as $update) {
                $inventory = $update['inventory'];
                $qty = $update['quantity'];

                $inventory->decrement('stock', $qty);

                StockMovement::create([
                    'src_store_id' => $storeId,
                    'dest_store_id' => null,
                    'product_id' => $inventory->product_id,
                    'quantity' => $qty,
                    'type' => 'out',
                    'title' => 'Order ' . $order->order_number,
                    'note' => 'Automatic deduction from order',
                ]);
            }

            // If not pay_later, automatically record the payment
            if ($data['payment_method'] !== 'pay_later' && $data['payment_method'] !== 'cod') {
                Payment::create([
                    'order_id' => $order->id,
                    'cashier_id' => $data['cashier_id'] ?? null,
                    'amount' => $totalAmount,
                    'payment_method' => $data['payment_method'],
                    'note' => 'Initial payment',
                ]);
            }

            return $order->load(['items.product', 'payments']);
        });
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(uniqid());
    }
}
