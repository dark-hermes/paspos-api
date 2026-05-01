<?php

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('revalidates inventory activity during checkout', function () {
    $member = User::factory()->create(['role' => 'member']);
    $store = Store::factory()->create();
    $category = ProductCategory::factory()->create();
    $brand = Brand::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
    ]);

    $inventory = Inventory::factory()->create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'stock' => 10,
        'purchase_price' => 5000,
        'selling_price' => 10000,
        'discount_percentage' => 0,
        'is_active' => true,
    ]);

    CartItem::query()->create([
        'user_id' => $member->id,
        'store_id' => $store->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $inventory->update(['is_active' => false]);

    $response = $this->actingAs($member)->postJson('/api/member/' . $store->id . '/cart/checkout', [
        'payment_method' => 'cod',
        'shipping_name' => 'PasPOS Delivery',
        'shipping_receiver_name' => 'John Doe',
        'shipping_receiver_phone' => '08123456789',
        'shipping_address' => 'Jl. Sudirman No. 10',
        'shipping_notes' => 'Ring bell once',
    ]);

    $response->assertUnprocessable();

    expect(Order::query()->count())->toBe(0);

    $this->assertDatabaseHas('cart_items', [
        'user_id' => $member->id,
        'store_id' => $store->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

it('creates online order from cart and stores base cost plus unit price snapshots', function () {
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
        'stock' => 10,
        'purchase_price' => 5000,
        'selling_price' => 10000,
        'discount_percentage' => 10,
        'is_active' => true,
    ]);

    CartItem::query()->create([
        'user_id' => $member->id,
        'store_id' => $store->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $response = $this->actingAs($member)->postJson('/api/member/' . $store->id . '/cart/checkout', [
        'payment_method' => 'cod',
        'shipping_name' => 'PasPOS Delivery',
        'shipping_receiver_name' => 'Jane Doe',
        'shipping_receiver_phone' => '089912345678',
        'shipping_address' => 'Jl. Gatot Subroto No. 20',
        'shipping_notes' => 'Leave at the reception',
    ]);

    $response->assertCreated();

    $order = Order::query()->first();

    expect($order)->not->toBeNull();
    expect($order?->type)->toBe('online');
    expect($order?->payment_method)->toBe('cod');
    expect($order?->payment_status)->toBe('unpaid');
    expect($order?->status)->toBe('pending');
    expect((float) $order?->shipping_fee)->toBe(0.0);
    expect((float) $order?->total_amount)->toBe(27000.0);

    $this->assertDatabaseHas('order_items', [
        'order_id' => $order?->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'base_cost' => 5000.00,
        'unit_price' => 9000.00,
        'subtotal' => 27000.00,
    ]);

    $this->assertDatabaseHas('inventories', [
        'store_id' => $store->id,
        'product_id' => $product->id,
        'stock' => 7.00,
    ]);

    expect(CartItem::query()->count())->toBe(0);
});
