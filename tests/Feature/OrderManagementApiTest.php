<?php

use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('recalculates total amount from absolute subtotal when shipping fee changes', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
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
        'stock' => 30,
        'purchase_price' => 10000,
        'selling_price' => 20000,
        'discount_percentage' => 0,
        'is_active' => true,
    ]);

    $member = User::factory()->create(['role' => 'member']);
    $order = app(OrderService::class)->createOrder([
        'type' => 'online',
        'store_id' => $store->id,
        'customer_id' => $member->id,
        'payment_method' => 'cod',
        'shipping_name' => 'PasPOS Delivery',
        'shipping_receiver_name' => 'Receiver',
        'shipping_receiver_phone' => '081234567890',
        'shipping_address' => 'Jl. Asia Afrika No. 1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ]);

    $this->actingAs($admin)->patchJson('/api/orders/' . $order->id . '/shipping', [
        'shipping_fee' => 10000,
        'courier_name' => 'Kurir A',
    ])->assertOk();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'processing',
        'shipping_fee' => 10000.00,
        'total_amount' => 50000.00,
    ]);

    $this->actingAs($admin)->patchJson('/api/orders/' . $order->id . '/shipping', [
        'shipping_fee' => 15000,
        'courier_name' => 'Kurir B',
    ])->assertOk();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'processing',
        'shipping_fee' => 15000.00,
        'total_amount' => 55000.00,
        'courier_name' => 'Kurir B',
    ]);
});

it('completes cod idempotently and prevents duplicate payments', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
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
        'stock' => 30,
        'purchase_price' => 10000,
        'selling_price' => 20000,
        'discount_percentage' => 0,
        'is_active' => true,
    ]);

    $member = User::factory()->create(['role' => 'member']);
    $order = app(OrderService::class)->createOrder([
        'type' => 'online',
        'store_id' => $store->id,
        'customer_id' => $member->id,
        'payment_method' => 'cod',
        'shipping_name' => 'PasPOS Delivery',
        'shipping_receiver_name' => 'Receiver',
        'shipping_receiver_phone' => '081234567890',
        'shipping_address' => 'Jl. Asia Afrika No. 1',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $this->actingAs($admin)->patchJson('/api/orders/' . $order->id . '/shipping', [
        'shipping_fee' => 5000,
        'courier_name' => 'Kurir COD',
    ])->assertOk();

    $this->actingAs($admin)->patchJson('/api/orders/' . $order->id . '/status', [
        'status' => 'shipped',
    ])->assertOk();

    $this->actingAs($admin)->postJson('/api/orders/' . $order->id . '/complete-cod')
        ->assertOk();

    $order->refresh();

    expect($order->status)->toBe('completed');
    expect($order->payment_status)->toBe('paid');
    expect(Payment::query()->where('order_id', $order->id)->count())->toBe(1);

    $this->actingAs($admin)->postJson('/api/orders/' . $order->id . '/complete-cod')
        ->assertUnprocessable();

    expect(Payment::query()->where('order_id', $order->id)->count())->toBe(1);
});

it('restricts branch_admin to manage only orders from their store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    
    $branchAdminA = User::factory()->create([
        'role' => 'branch_admin',
        'store_id' => $storeA->id
    ]);
    
    $member = User::factory()->create(['role' => 'member']);
    
    // Create order in store B
    $orderB = Order::factory()->create([
        'store_id' => $storeB->id,
        'customer_id' => $member->id,
        'type' => 'online',
        'status' => 'pending'
    ]);
    
    // Branch Admin A tries to update shipping of Order B (Forbidden)
    $this->actingAs($branchAdminA)->patchJson("/api/orders/{$orderB->id}/shipping", [
        'shipping_fee' => 10000,
        'courier_name' => 'Kurir A'
    ])->assertForbidden();
    
    // Create order in store A
    $orderA = Order::factory()->create([
        'store_id' => $storeA->id,
        'customer_id' => $member->id,
        'type' => 'online',
        'status' => 'pending'
    ]);
    
    // Branch Admin A tries to update shipping of Order A (Allowed)
    $this->actingAs($branchAdminA)->patchJson("/api/orders/{$orderA->id}/shipping", [
        'shipping_fee' => 10000,
        'courier_name' => 'Kurir A'
    ])->assertOk();
});

it('denies cashier to manage online orders', function () {
    $store = Store::factory()->create();
    $cashier = User::factory()->create([
        'role' => 'cashier',
        'store_id' => $store->id
    ]);
    
    $member = User::factory()->create(['role' => 'member']);
    $order = Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $member->id,
        'type' => 'online',
        'status' => 'pending'
    ]);
    
    $this->actingAs($cashier)->patchJson("/api/orders/{$order->id}/shipping", [
        'shipping_fee' => 10000,
        'courier_name' => 'Kurir'
    ])->assertForbidden();
});
