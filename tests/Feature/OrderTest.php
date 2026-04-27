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
use App\Services\OrderService;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $store;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        $this->admin = User::factory()->create(['role' => 'main_admin', 'store_id' => $this->store->id]);

        $category = ProductCategory::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        Inventory::factory()->create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'stock' => 50,
            'purchase_price' => 5000,
            'selling_price' => 8000,
            'discount_percentage' => 0,
        ]);

        $service = app(OrderService::class);
        $this->order = $service->createOrder([
            'type' => 'pos',
            'store_id' => $this->store->id,
            'cashier_id' => $this->admin->id,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);
    }

    public function test_can_list_orders()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/orders');

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.id', $this->order->id);
    }

    public function test_can_filter_orders_by_store()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/orders?store_id=' . $this->store->id);

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_orders_by_payment_status()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/orders?payment_status=paid');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');

        $response = $this->actingAs($this->admin)->getJson('/api/orders?payment_status=unpaid');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    public function test_can_show_order_detail()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/orders/' . $this->order->id);

        $response->assertStatus(200)
                 ->assertJsonPath('data.order_number', $this->order->order_number)
                 ->assertJsonStructure([
                     'data' => [
                         'id', 'order_number', 'type', 'total_amount',
                         'payment_status', 'status', 'items', 'payments'
                     ]
                 ]);
    }

    public function test_branch_admin_only_sees_their_store_orders()
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        
        $adminA = User::factory()->create(['role' => 'branch_admin', 'store_id' => $storeA->id]);
        
        Order::factory()->count(3)->create(['store_id' => $storeA->id]);
        Order::factory()->count(2)->create(['store_id' => $storeB->id]);
        
        $response = $this->actingAs($adminA)->getJson('/api/orders');
        
        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_branch_admin_cannot_show_other_store_order()
    {
        $storeA = Store::factory()->create();
        $storeB = Store::factory()->create();
        
        $adminA = User::factory()->create(['role' => 'branch_admin', 'store_id' => $storeA->id]);
        $orderB = Order::factory()->create(['store_id' => $storeB->id]);
        
        $this->actingAs($adminA)->getJson('/api/orders/' . $orderB->id)
             ->assertForbidden();
    }
}
