<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Sembako'],
            ['name' => 'Mie Instan'],
            ['name' => 'Makanan Ringan'],
            ['name' => 'Minuman'],
            ['name' => 'Bumbu Masak'],
            ['name' => 'Perlengkapan Mandi'],
            ['name' => 'Perlengkapan Cuci'],
        ];

        foreach ($categories as $category) {
            ProductCategory::query()->create($category);
        }
    }
}
