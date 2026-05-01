<?php

use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only branch stores for public branch selection', function () {
    $branchStore = Store::factory()->create(['type' => 'branch']);
    $mainStore = Store::factory()->create(['type' => 'main']);

    $response = $this->getJson('/api/member/branches');

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $branchStore->id)
        ->assertJsonMissing(['id' => $mainStore->id]);
});

it('returns 404 for non-existent branch in catalog', function () {
    $this->getJson('/api/member/999/catalog/products')
        ->assertNotFound();
});

it('allows public access to catalog without authentication', function () {
    $store = Store::factory()->create(['type' => 'branch']);
    $category = ProductCategory::factory()->create(['name' => 'Makanan']);
    $brand = Brand::factory()->create(['name' => 'Indofood']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Indomie Goreng',
        'sku' => 'SKU-INDOMIE-001',
        'barcode' => '8991111111111',
    ]);

    Inventory::factory()->create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'stock' => 25,
        'purchase_price' => 6000,
        'selling_price' => 9000,
        'discount_percentage' => 10,
        'min_stock' => 5,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/member/'.$store->id.'/catalog/products');

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Indomie Goreng')
        ->assertJsonPath('data.0.stock', 25)
        ->assertJsonPath('data.0.selling_price', 9000)
        ->assertJsonPath('data.0.discount_percentage', 10)
        ->assertJsonPath('data.0.final_price', 8100);

    $response->assertJsonMissingPath('data.0.purchase_price');
    $response->assertJsonMissingPath('data.0.min_stock');
    $response->assertJsonMissingPath('data.0.store_id');
    $response->assertJsonMissingPath('data.0.product_id');
    $response->assertJsonMissingPath('data.0.category_id');
    $response->assertJsonMissingPath('data.0.brand_id');
    $response->assertJsonMissingPath('data.0.created_at');
    $response->assertJsonMissingPath('data.0.updated_at');
});

it('scopes products catalog to the selected branch only', function () {
    $storeA = Store::factory()->create(['type' => 'branch']);
    $storeB = Store::factory()->create(['type' => 'branch']);
    $category = ProductCategory::factory()->create(['name' => 'Makanan']);
    $brand = Brand::factory()->create(['name' => 'Indofood']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Indomie Goreng',
    ]);

    Inventory::factory()->create([
        'store_id' => $storeA->id,
        'product_id' => $product->id,
        'stock' => 25,
        'selling_price' => 9000,
        'purchase_price' => 6000,
        'is_active' => true,
    ]);

    Inventory::factory()->create([
        'store_id' => $storeB->id,
        'product_id' => $product->id,
        'stock' => 100,
        'selling_price' => 5000,
        'purchase_price' => 3000,
        'is_active' => true,
    ]);

    $responseA = $this->getJson('/api/member/'.$storeA->id.'/catalog/products');
    $responseA->assertOk()->assertJsonPath('data.0.stock', 25);

    $responseB = $this->getJson('/api/member/'.$storeB->id.'/catalog/products');
    $responseB->assertOk()->assertJsonPath('data.0.stock', 100);
});

it('scopes categories and brands to the selected branch', function () {
    $storeA = Store::factory()->create(['type' => 'branch']);
    $storeB = Store::factory()->create(['type' => 'branch']);

    $categoryA = ProductCategory::factory()->create(['name' => 'Makanan']);
    $categoryB = ProductCategory::factory()->create(['name' => 'Minuman']);
    $brandA = Brand::factory()->create(['name' => 'Indofood']);
    $brandB = Brand::factory()->create(['name' => 'Unilever']);

    $productA = Product::factory()->create([
        'category_id' => $categoryA->id,
        'brand_id' => $brandA->id,
    ]);

    $productB = Product::factory()->create([
        'category_id' => $categoryB->id,
        'brand_id' => $brandB->id,
    ]);

    Inventory::factory()->create([
        'store_id' => $storeA->id,
        'product_id' => $productA->id,
        'stock' => 10,
        'selling_price' => 8000,
        'purchase_price' => 6000,
        'is_active' => true,
    ]);

    Inventory::factory()->create([
        'store_id' => $storeB->id,
        'product_id' => $productB->id,
        'stock' => 10,
        'selling_price' => 9000,
        'purchase_price' => 7000,
        'is_active' => true,
    ]);

    $categoriesResponse = $this->getJson('/api/member/'.$storeA->id.'/catalog/categories');

    $categoriesResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Makanan')
        ->assertJsonPath('data.0.products_count', 1);

    $brandsResponse = $this->getJson('/api/member/'.$storeA->id.'/catalog/brands');

    $brandsResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Indofood')
        ->assertJsonPath('data.0.products_count', 1);
});

it('supports search across products by name, sku, barcode, brand, and category', function () {
    $store = Store::factory()->create(['type' => 'branch']);
    $category1 = ProductCategory::factory()->create(['name' => 'Makanan']);
    $category2 = ProductCategory::factory()->create(['name' => 'Minuman']);
    $brand1 = Brand::factory()->create(['name' => 'Indofood']);
    $brand2 = Brand::factory()->create(['name' => 'Unilever']);

    $product1 = Product::factory()->create([
        'category_id' => $category1->id,
        'brand_id' => $brand1->id,
        'name' => 'Indomie Goreng',
        'sku' => 'SKU-INDO-001',
    ]);

    $product2 = Product::factory()->create([
        'category_id' => $category2->id,
        'brand_id' => $brand2->id,
        'name' => 'Aqua Botol',
        'sku' => 'SKU-AQUA-001',
    ]);

    Inventory::factory()->create([
        'store_id' => $store->id,
        'product_id' => $product1->id,
        'stock' => 10,
        'is_active' => true,
    ]);

    Inventory::factory()->create([
        'store_id' => $store->id,
        'product_id' => $product2->id,
        'stock' => 20,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/member/'.$store->id.'/catalog/products?search=Indomie');
    $response->assertOk()->assertJsonCount(1, 'data');

    $response2 = $this->getJson('/api/member/'.$store->id.'/catalog/products?search=Indofood');
    $response2->assertOk()->assertJsonCount(1, 'data');

    $response3 = $this->getJson('/api/member/'.$store->id.'/catalog/products?search=Makanan');
    $response3->assertOk()->assertJsonCount(1, 'data');
});

it('shows only active inventory products in the catalog', function () {
    $store = Store::factory()->create(['type' => 'branch']);
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    Inventory::factory()->create([
        'store_id' => $store->id,
        'product_id' => $product1->id,
        'stock' => 10,
        'is_active' => true,
    ]);

    Inventory::factory()->create([
        'store_id' => $store->id,
        'product_id' => $product2->id,
        'stock' => 20,
        'is_active' => false,
    ]);

    $response = $this->getJson('/api/member/'.$store->id.'/catalog/products');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $product1->id);
});
