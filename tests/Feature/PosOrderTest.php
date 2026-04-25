<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;

class PosOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $store;
    private Product $product;
    private Inventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        $this->admin = User::factory()->create(['role' => 'cashier', 'store_id' => $this->store->id]);
        
        $category = ProductCategory::factory()->create();
        $brand = Brand::factory()->create();
        
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'barcode' => '123456789',
            'name' => 'Test Product'
        ]);
        
        $this->inventory = Inventory::factory()->create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'stock' => 10,
            'purchase_price' => 50000,
            'selling_price' => 60000,
            'discount_percentage' => 0,
        ]);
    }

    public function test_can_search_product_by_barcode()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/pos/products?store_id=' . $this->store->id . '&search=123456789');

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.product.id', $this->product->id);
    }

    public function test_can_place_pos_cash_order_and_reduces_stock()
    {
        $payload = [
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 1000 // Sent from frontend, should be ignored
                ]
            ]
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/pos/orders', $payload);

        $response->assertStatus(201);
        
        $order = Order::first();
        $this->assertEquals(120000, $order->total_amount); // 2 * 60000 (selling_price)
        $this->assertEquals('paid', $order->payment_status);

        $this->assertDatabaseHas('inventories', [
            'id' => $this->inventory->id,
            'stock' => 8 // 10 - 2
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 2
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 120000
        ]);
        
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'base_cost' => 50000,
            'unit_price' => 60000,
            'subtotal' => 120000
        ]);
    }

    public function test_can_place_pos_pay_later_order()
    {
        $customer = User::factory()->create(['role' => 'member']);

        $payload = [
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'payment_method' => 'pay_later',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1
                ]
            ]
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/pos/orders', $payload);

        $response->assertStatus(201);
        
        $order = Order::first();
        $this->assertEquals('unpaid', $order->payment_status);

        // No payment should be created initially
        $this->assertEquals(0, Payment::count());
    }

    public function test_pay_later_fails_without_customer()
    {
        $payload = [
            'store_id' => $this->store->id,
            'payment_method' => 'pay_later',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1
                ]
            ]
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/pos/orders', $payload);

        $response->assertStatus(422);
    }
}
