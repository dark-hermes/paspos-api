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
use App\Services\OrderService;

class SalesDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $store;

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
        $service->createOrder([
            'type' => 'pos',
            'store_id' => $this->store->id,
            'cashier_id' => $this->admin->id,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);
    }

    public function test_dashboard_summary_returns_metric_changes_and_chart_data()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/orders/dashboard');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'daily' => [
                             'total_omzet',
                             'total_omzet_change',
                             'total_transactions',
                             'total_transactions_change',
                             'products_sold',
                             'products_sold_change',
                             'average_transaction_amount',
                             'average_transaction_amount_change',
                         ],
                         'weekly' => [
                             'total_omzet',
                             'total_omzet_change',
                             'total_transactions',
                             'total_transactions_change',
                             'products_sold',
                             'products_sold_change',
                             'average_transaction_amount',
                             'average_transaction_amount_change',
                         ],
                         'monthly' => [
                             'total_omzet',
                             'total_omzet_change',
                             'total_transactions',
                             'total_transactions_change',
                             'products_sold',
                             'products_sold_change',
                             'average_transaction_amount',
                             'average_transaction_amount_change',
                         ],
                         'chart' => [
                             'daily' => [
                                 'labels',
                                 'datasets',
                             ],
                             'weekly' => [
                                 'labels',
                                 'datasets',
                             ],
                             'monthly' => [
                                 'labels',
                                 'datasets',
                             ],
                         ],
                         'recent_transactions' => [
                             '*' => [
                                 'order_number',
                                 'type',
                                 'total_amount',
                                 'payment_method',
                                 'payment_status',
                                 'status',
                                 'created_at',
                             ],
                         ],
                         'top_debtors' => [
                             '*' => [
                                 'customer_id',
                                 'customer_name',
                                 'customer_email',
                                 'customer_phone',
                                 'total_due',
                                 'order_count',
                             ],
                         ],
                     ],
                 ])
                 ->assertJsonCount(1, 'data.chart.daily.datasets')
                 ->assertJsonCount(1, 'data.chart.weekly.datasets')
                 ->assertJsonCount(1, 'data.chart.monthly.datasets');
    }
}
