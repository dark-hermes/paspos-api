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
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Services\OrderService;
use Carbon\Carbon;

class CancelExpiredOnlineOrdersTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        $category = ProductCategory::factory()->create();
        $brand = Brand::factory()->create();

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        Inventory::factory()->create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'stock' => 100,
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'discount_percentage' => 0,
        ]);
    }

    public function test_cancels_unpaid_online_orders_older_than_24_hours()
    {
        $customer = User::factory()->create(['role' => 'member']);

        // Manually create an unpaid online order (simulating a COD / awaiting-payment scenario)
        $order = Order::create([
            'order_number' => 'ORD-EXPIRED',
            'type' => 'online',
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'total_amount' => 50000,
            'payment_method' => 'transfer',
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'created_at' => Carbon::now()->subHours(25),
            'updated_at' => Carbon::now()->subHours(25),
        ]);

        // Create order item
        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 5,
            'base_cost' => 5000,
            'unit_price' => 10000,
            'subtotal' => 50000,
        ]);

        // Manually deduct stock to simulate the initial stock deduction
        Inventory::where('store_id', $this->store->id)
            ->where('product_id', $this->product->id)
            ->decrement('stock', 5);

        // Confirm stock is at 95
        $this->assertDatabaseHas('inventories', [
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'stock' => 95.00,
        ]);

        // Run the command
        $this->artisan('orders:cancel-expired')
             ->assertSuccessful();

        // Order should now be cancelled
        $this->assertEquals('cancelled', $order->fresh()->status);

        // Stock should be restored to 100
        $this->assertDatabaseHas('inventories', [
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'stock' => 100.00,
        ]);

        // A stock movement IN should be recorded
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 5,
        ]);
    }

    public function test_does_not_cancel_recent_unpaid_orders()
    {
        $customer = User::factory()->create(['role' => 'member']);

        // Create an online order that's recent (not expired)
        $order = Order::create([
            'order_number' => 'ORD-RECENT',
            'type' => 'online',
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'total_amount' => 10000,
            'payment_method' => 'transfer',
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);

        $this->artisan('orders:cancel-expired')
             ->assertSuccessful();

        // Order should still be pending
        $this->assertEquals('pending', $order->fresh()->status);
    }

    public function test_does_not_cancel_paid_online_orders()
    {
        $customer = User::factory()->create(['role' => 'member']);

        $order = Order::create([
            'order_number' => 'ORD-PAID',
            'type' => 'online',
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'total_amount' => 10000,
            'payment_method' => 'transfer',
            'payment_status' => 'paid',
            'status' => 'pending',
            'created_at' => Carbon::now()->subHours(48),
        ]);

        $this->artisan('orders:cancel-expired')
             ->assertSuccessful();

        // Order should still be pending (not cancelled) because it's paid
        $this->assertEquals('pending', $order->fresh()->status);
    }
}
