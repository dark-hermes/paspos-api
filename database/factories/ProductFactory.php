<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => ProductCategory::factory(),
            'brand_id' => Brand::factory(),
            'name' => fake()->words(3, true),
            'barcode' => fake()->unique()->ean13(),
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'unit' => fake()->randomElement(['pcs', 'kg', 'ltr', 'box', 'pack']),
            'weight' => fake()->optional()->randomFloat(2, 1, 50000),
            'description' => fake()->sentence(),
        ];
    }
}
