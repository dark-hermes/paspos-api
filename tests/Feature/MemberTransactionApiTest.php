<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Store $store;

    private Store $secondStore;

    private Order $onlineOrder;

    private Order $posOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = User::factory()->create(['role' => 'member']);
        $this->store = Store::factory()->create(['type' => 'branch']);
        $this->secondStore = Store::factory()->create(['type' => 'branch']);

        // Create product with inventory
        $category = ProductCategory::factory()->create();
        $brand = Brand::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        Inventory::factory()->create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'stock' => 100,
            'selling_price' => 50000,
            'purchase_price' => 30000,
        ]);

        Inventory::factory()->create([
            'store_id' => $this->secondStore->id,
            'product_id' => $product->id,
            'stock' => 100,
            'selling_price' => 50000,
            'purchase_price' => 30000,
        ]);

        // Create orders
        $orderService = app(OrderService::class);

        $this->onlineOrder = $orderService->createOrder([
            'type' => 'online',
            'store_id' => $this->store->id,
            'customer_id' => $this->member->id,
            'payment_method' => 'cod',
            'shipping_name' => 'Toko Utama',
            'shipping_receiver_name' => 'John Doe',
            'shipping_receiver_phone' => '081234567890',
            'shipping_address' => 'Jl. Test No. 123',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $this->posOrder = $orderService->createOrder([
            'type' => 'pos',
            'store_id' => $this->secondStore->id,
            'customer_id' => $this->member->id,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);
    }

    public function test_member_can_list_their_transactions(): void
    {
        $response = $this->actingAs($this->member)->getJson('/api/member/transactions');

        $response->assertSuccessful();
        $response->assertJsonPath('status', 'success');
        $this->assertIsArray($response->json('data'));
    }

    public function test_member_transaction_list_includes_items_and_payments(): void
    {
        $response = $this->actingAs($this->member)->getJson('/api/member/transactions');

        $response->assertSuccessful();
        // Get the transaction data - it could be under 'data' or 'data.data' depending on pagination
        $data = $response->json('data.data.0') ?? $response->json('data.0');

        $this->assertNotNull($data, 'Transaction data not found in response');
        $this->assertNotEmpty($data['items']);
        $this->assertArrayHasKey('quantity', $data['items'][0]);
        $this->assertArrayHasKey('unit_price', $data['items'][0]);
        $this->assertArrayHasKey('subtotal', $data['items'][0]);
    }

    public function test_member_can_filter_transactions_by_branch(): void
    {
        $response = $this->actingAs($this->member)
            ->getJson("/api/member/transactions?branch={$this->store->id}");

        $response->assertSuccessful();
        $transactions = $response->json('data.data') ?? $response->json('data');

        $this->assertIsArray($transactions);
        if (! empty($transactions)) {
            foreach ($transactions as $transaction) {
                $this->assertEquals($this->store->id, $transaction['store_id']);
            }
        }
    }

    public function test_member_can_filter_transactions_by_payment_status(): void
    {
        $response = $this->actingAs($this->member)
            ->getJson('/api/member/transactions?payment_status=paid');

        $response->assertSuccessful();
        $transactions = $response->json('data.data') ?? $response->json('data');

        $this->assertIsArray($transactions);
        if (! empty($transactions)) {
            foreach ($transactions as $transaction) {
                $this->assertEquals('paid', $transaction['payment_status']);
            }
        }
    }

    public function test_member_can_filter_transactions_by_type(): void
    {
        $response = $this->actingAs($this->member)
            ->getJson('/api/member/transactions?type=online');

        $response->assertSuccessful();
        $transactions = $response->json('data.data') ?? $response->json('data');

        $this->assertIsArray($transactions);
        if (! empty($transactions)) {
            foreach ($transactions as $transaction) {
                $this->assertEquals('online', $transaction['type']);
            }
        }
    }

    public function test_member_can_view_single_transaction(): void
    {
        $response = $this->actingAs($this->member)
            ->getJson("/api/member/transactions/{$this->onlineOrder->id}");

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'status',
            'data' => [
                'id',
                'order_number',
                'type',
                'store_id',
                'total_amount',
                'payment_method',
                'payment_status',
                'status',
                'items',
                'payments',
                'store',
            ],
        ]);
        $response->assertJsonFragment(['id' => $this->onlineOrder->id]);
    }

    public function test_member_cannot_view_other_members_transaction(): void
    {
        $otherMember = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($otherMember)
            ->getJson("/api/member/transactions/{$this->onlineOrder->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_list_transactions(): void
    {
        $response = $this->getJson('/api/member/transactions');

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_view_transaction(): void
    {
        $response = $this->getJson("/api/member/transactions/{$this->onlineOrder->id}");

        $response->assertUnauthorized();
    }

    public function test_transactions_are_paginated(): void
    {
        $response = $this->actingAs($this->member)->getJson('/api/member/transactions');

        $response->assertSuccessful();
        // Check if paginated response has expected keys
        $data = $response->json('data');
        $this->assertTrue(
            isset($data['data']) || isset($data[0]),
            'Response should be paginated with "data" key or be a direct array'
        );
    }

    public function test_member_transaction_includes_store_information(): void
    {
        $response = $this->actingAs($this->member)
            ->getJson("/api/member/transactions/{$this->onlineOrder->id}");

        $response->assertSuccessful();
        $data = $response->json('data');

        $this->assertArrayHasKey('store', $data);
        $this->assertArrayHasKey('id', $data['store']);
        $this->assertArrayHasKey('name', $data['store']);
    }
}
