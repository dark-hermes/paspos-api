<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'product_id' => Product::factory(),
            'stock' => fake()->randomFloat(2, 0, 1000),
            'purchase_price' => fake()->randomFloat(2, 1000, 50000),
            'selling_price' => fake()->randomFloat(2, 2000, 100000),
            'discount_percentage' => fake()->numberBetween(0, 50),
            'min_stock' => fake()->randomFloat(2, 1, 10),
        ];
    }
}
