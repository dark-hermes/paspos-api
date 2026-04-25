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
use App\Services\OrderService;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Store $store;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        $this->cashier = User::factory()->create(['role' => 'cashier', 'store_id' => $this->store->id]);

        $category = ProductCategory::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        Inventory::factory()->create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'stock' => 20,
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'discount_percentage' => 0,
        ]);

        // Create a pay_later order via service
        $customer = User::factory()->create(['role' => 'member']);
        $service = app(OrderService::class);
        $this->order = $service->createOrder([
            'type' => 'pos',
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'cashier_id' => $this->cashier->id,
            'payment_method' => 'pay_later',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);
    }

    public function test_pay_later_order_starts_as_unpaid()
    {
        $this->assertEquals('unpaid', $this->order->payment_status);
        $this->assertEquals(45000, $this->order->total_amount); // 3 * 15000
        $this->assertEquals(0, Payment::count());
    }

    public function test_partial_payment_sets_status_to_partial()
    {
        $response = $this->actingAs($this->cashier)->postJson('/api/payments', [
            'order_id' => $this->order->id,
            'amount' => 20000,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('partial', $this->order->fresh()->payment_status);
    }

    public function test_full_payment_sets_status_to_paid()
    {
        // Pay in two installments
        $this->actingAs($this->cashier)->postJson('/api/payments', [
            'order_id' => $this->order->id,
            'amount' => 20000,
            'payment_method' => 'cash',
        ]);

        $this->actingAs($this->cashier)->postJson('/api/payments', [
            'order_id' => $this->order->id,
            'amount' => 25000,
            'payment_method' => 'transfer',
        ]);

        $this->assertEquals('paid', $this->order->fresh()->payment_status);
        $this->assertEquals(2, Payment::count());
    }

    public function test_overpayment_is_rejected()
    {
        $response = $this->actingAs($this->cashier)->postJson('/api/payments', [
            'order_id' => $this->order->id,
            'amount' => 99999,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'Payment amount exceeds the remaining order balance.']);
    }

    public function test_can_list_payments_for_order()
    {
        Payment::create([
            'order_id' => $this->order->id,
            'cashier_id' => $this->cashier->id,
            'amount' => 10000,
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($this->cashier)->getJson('/api/payments?order_id=' . $this->order->id);

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }
}
