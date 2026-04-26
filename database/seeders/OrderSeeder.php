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
        $members = $store ? User::query()->where('role', 'member')->where('store_id', $store->id)->get() : collect();
        $inventories = $store ? Inventory::query()->where('store_id', $store->id)->get() : collect();

        if (! $store || ! $cashier || $inventories->count() < 3) {
            return;
        }

        if ($members->count() < 3) {
            User::factory()->count(3 - $members->count())->assignStore($store->id)->withAddress()->create([
                'role' => 'member',
            ]);

            $members = User::query()->where('role', 'member')->where('store_id', $store->id)->get();
        }

        $members = $members->shuffle()->values();
        $items = $inventories->shuffle()->take(3)->values();

        $this->createOrder(
            $store->id,
            $members[0]->id,
            $cashier->id,
            'pos',
            'cash',
            'paid',
            'completed',
            Carbon::today()->setTime(9, 0),
            [
                ['inventory' => $items[0], 'quantity' => 2],
                ['inventory' => $items[1], 'quantity' => 1],
            ],
            [],
            null,
        );

        $this->createOrder(
            $store->id,
            $members[1]->id,
            $cashier->id,
            'pos',
            'transfer',
            'partial',
            'processing',
            Carbon::today()->subDays(2)->setTime(14, 30),
            [
                ['inventory' => $items[1], 'quantity' => 4],
                ['inventory' => $items[2], 'quantity' => 2],
            ],
            [],
            20000,
        );

        $this->createOrder(
            $store->id,
            $members[2]->id,
            $cashier->id,
            'pos',
            'cash',
            'unpaid',
            'pending',
            Carbon::today()->subDays(3)->setTime(16, 15),
            [
                ['inventory' => $items[0], 'quantity' => 5],
            ],
            [],
            null,
        );

        $this->createOrder(
            $store->id,
            $members[1]->id,
            $cashier->id,
            'online',
            'pay_later',
            'unpaid',
            'pending',
            Carbon::today()->subDays(7)->setTime(11, 15),
            [
                ['inventory' => $items[2], 'quantity' => 3],
            ],
            [
                'shipping_name' => 'Amazing Store',
                'shipping_receiver_name' => $members[1]->name,
                'shipping_receiver_phone' => $members[1]->phone,
                'shipping_address' => 'Jl. Pelanggan No. 45, Jakarta',
                'shipping_notes' => 'Tinggal di meja kasir.',
            ],
            null,
        );

        $this->createOrder(
            $store->id,
            $members[0]->id,
            $cashier->id,
            'pos',
            'qris',
            'partial',
            'completed',
            Carbon::today()->subDays(1)->setTime(10, 0),
            [
                ['inventory' => $items[0], 'quantity' => 1],
                ['inventory' => $items[2], 'quantity' => 2],
            ],
            [],
            15000,
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
        array $shipping = [],
        ?float $paymentAmount = null
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

        if ($paymentAmount !== null) {
            Payment::create([
                'order_id' => $order->id,
                'cashier_id' => $cashierId,
                'amount' => min($paymentAmount, $totalAmount),
                'payment_method' => $paymentMethod,
                'note' => 'Seeded payment for order ' . $order->order_number,
            ]);
        }
    }
}
