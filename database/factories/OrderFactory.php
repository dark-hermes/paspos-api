<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . strtoupper($this->faker->unique()->bothify('??####')),
            'type' => $this->faker->randomElement(['pos', 'online']),
            'store_id' => Store::factory(),
            'customer_id' => User::factory(),
            'cashier_id' => User::factory(),
            'total_amount' => $this->faker->randomFloat(2, 10000, 1000000),
            'shipping_fee' => 0,
            'payment_method' => $this->faker->randomElement(['cash', 'transfer', 'qris', 'cod', 'pay_later']),
            'payment_status' => $this->faker->randomElement(['paid', 'unpaid', 'partial']),
            'status' => $this->faker->randomElement(['completed', 'pending', 'processing', 'shipped', 'delivered', 'cancelled']),
        ];
    }
}
