<?php

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('validates selected product and store inventory when adding to cart', function () {
    $member = User::factory()->create(['role' => 'member']);
    $store = Store::factory()->create();
    $category = ProductCategory::factory()->create();
    $brand = Brand::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
    ]);

    $response = $this->actingAs($member)->postJson('/api/member/cart', [
        'store_id' => $store->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response->assertUnprocessable()
        ->assertJsonFragment([
            'message' => 'Selected product is not available in the selected store inventory.',
        ]);
});

it('keeps one cart row per user-store-product combination', function () {
    $member = User::factory()->create(['role' => 'member']);
    $store = Store::factory()->create();
    $category = ProductCategory::factory()->create();
    $brand = Brand::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
    ]);

    Inventory::factory()->create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'stock' => 50,
        'purchase_price' => 8000,
        'selling_price' => 12000,
        'discount_percentage' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($member)->postJson('/api/member/cart', [
        'store_id' => $store->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertCreated();

    $this->actingAs($member)->postJson('/api/member/cart', [
        'store_id' => $store->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ])->assertOk();

    expect(CartItem::query()->count())->toBe(1);

    $this->assertDatabaseHas('cart_items', [
        'user_id' => $member->id,
        'store_id' => $store->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);
});

it('returns realtime stock and price from inventory on cart listing', function () {
    $member = User::factory()->create(['role' => 'member']);
    $store = Store::factory()->create();
    $category = ProductCategory::factory()->create();
    $brand = Brand::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
    ]);

    Inventory::factory()->create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'stock' => 20,
        'purchase_price' => 7000,
        'selling_price' => 10000,
        'discount_percentage' => 20,
        'is_active' => true,
    ]);

    CartItem::query()->create([
        'user_id' => $member->id,
        'store_id' => $store->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response = $this->actingAs($member)->getJson('/api/member/cart');

    $response->assertOk()
        ->assertJsonPath('data.0.current_stock', 20)
        ->assertJsonPath('data.0.current_unit_price', 8000)
        ->assertJsonPath('data.0.line_subtotal', 16000);
});
