<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();
        $products = Product::all();

        foreach ($stores as $store) {
            foreach ($products as $product) {
                Inventory::query()->create([
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'purchase_price' => 2000,
                    'selling_price' => 3000,
                    'stock' => rand(0, 100),
                    'min_stock' => rand(0, 10),
                ]);
            }
        }
    }
}
