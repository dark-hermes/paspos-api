<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('type', 'branch')->first();
        $cashier = $store ? User::query()->where('role', 'cashier')->where('store_id', $store->id)->first() : null;
        $customer = $store ? User::query()->where('role', 'member')->where('store_id', $store->id)->first() : null;
        $inventories = $store ? Inventory::query()->where('store_id', $store->id)->get() : collect();

        if (! $store || ! $cashier || ! $customer || $inventories->count() < 2) {
            return;
        }

        $items = $inventories->shuffle()->take(2)->values();

        $this->createOrder(
            $store->id,
            $customer->id,
            $cashier->id,
            'pos',
            'cash',
            'paid',
            'completed',
            Carbon::today()->setTime(9, 0),
            [
                ['inventory' => $items[0], 'quantity' => 3],
                ['inventory' => $items[1], 'quantity' => 1],
            ],
        );

        $this->createOrder(
            $store->id,
            $customer->id,
            $cashier->id,
            'pos',
            'transfer',
            'paid',
            'completed',
            Carbon::today()->subDays(2)->setTime(14, 30),
            [
                ['inventory' => $items[1], 'quantity' => 2],
            ],
        );

        $this->createOrder(
            $store->id,
            $customer->id,
            $cashier->id,
            'online',
            'qris',
            'paid',
            'processing',
            Carbon::today()->startOfMonth()->addDays(2)->setTime(11, 15),
            [
                ['inventory' => $items[0], 'quantity' => 2],
                ['inventory' => $items[1], 'quantity' => 2],
            ],
            [
                'shipping_name' => 'Amazing Store',
                'shipping_receiver_name' => $customer->name,
                'shipping_receiver_phone' => $customer->phone,
                'shipping_address' => 'Jl. Example No. 123, Jakarta',
                'shipping_notes' => 'Leave package at the cashier desk.',
            ],
        );
    }

    private function createOrder(
        int $storeId,
        int $customerId,
        int $cashierId,
        string $type,
        string $paymentMethod,
        string $paymentStatus,
        string $status,
        Carbon $date,
        array $items,
        array $shipping = []
    ): void {
        $orderItems = [];
        $totalAmount = 0;

        foreach ($items as $itemData) {
            $inventory = $itemData['inventory'];
            $quantity = $itemData['quantity'];
            $subtotal = (float) $inventory->selling_price * $quantity;

            $orderItems[] = [
                'product_id' => $inventory->product_id,
                'quantity' => $quantity,
                'base_cost' => $inventory->purchase_price,
                'unit_price' => $inventory->selling_price,
                'subtotal' => $subtotal,
            ];

            $totalAmount += $subtotal;
        }

        $order = Order::create(array_merge([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'type' => $type,
            'store_id' => $storeId,
            'customer_id' => $customerId,
            'cashier_id' => $cashierId,
            'total_amount' => $totalAmount,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'status' => $status,
            'created_at' => $date,
            'updated_at' => $date,
        ], $shipping));

        $order->items()->createMany($orderItems);

        Payment::create([
            'order_id' => $order->id,
            'cashier_id' => $cashierId,
            'amount' => $totalAmount,
            'payment_method' => $paymentMethod,
            'note' => 'Seeded payment for order ' . $order->order_number,
        ]);
    }
}
