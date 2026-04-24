<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to perform product crud', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;

    $category = ProductCategory::factory()->create(['name' => 'Makanan']);
    $brand = Brand::factory()->create(['name' => 'Indofood']);

    // Create
    $createResponse = $this
        ->withToken($token)
        ->postJson('/api/products', [
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Indomie Goreng',
            'sku' => 'SKU-001',
            'unit' => 'pcs',
            'description' => 'Mie goreng instant',
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Indomie Goreng')
        ->assertJsonPath('data.sku', 'SKU-001')
        ->assertJsonPath('data.category.id', $category->id)
        ->assertJsonPath('data.brand.id', $brand->id);

    $productId = $createResponse->json('data.id');

    // Index with eager-loaded relations
    $indexResponse = $this
        ->withToken($token)
        ->getJson('/api/products');

    $indexResponse
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect($indexResponse->json('data.0.category'))->not->toBeNull();
    expect($indexResponse->json('data.0.brand'))->not->toBeNull();

    // Show
    $this
        ->withToken($token)
        ->getJson('/api/products/' . $productId)
        ->assertOk()
        ->assertJsonPath('data.id', $productId);

    // Update
    $this
        ->withToken($token)
        ->patchJson('/api/products/' . $productId, [
            'name' => 'Indomie Soto',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Indomie Soto');

    // Delete
    $this
        ->withToken($token)
        ->deleteJson('/api/products/' . $productId)
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect(Product::query()->whereKey($productId)->exists())->toBeFalse();
});

it('filters products by category and brand', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;

    $cat1 = ProductCategory::factory()->create(['name' => 'Makanan']);
    $cat2 = ProductCategory::factory()->create(['name' => 'Minuman']);
    $brand = Brand::factory()->create();

    Product::factory()->create(['category_id' => $cat1->id, 'brand_id' => $brand->id]);
    Product::factory()->create(['category_id' => $cat2->id, 'brand_id' => $brand->id]);

    $response = $this
        ->withToken($token)
        ->getJson('/api/products?category_id=' . $cat1->id);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('searches products by name and sku', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;

    Product::factory()->create(['name' => 'Indomie Goreng', 'sku' => 'SKU-INDOMIE-001']);
    Product::factory()->create(['name' => 'Aqua Botol', 'sku' => 'SKU-AQUA-001']);

    $response = $this
        ->withToken($token)
        ->getJson('/api/products?search=Indomie');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);

    $response2 = $this
        ->withToken($token)
        ->getJson('/api/products?search=SKU-AQUA');

    $response2->assertOk();
    expect($response2->json('data'))->toHaveCount(1);
});

it('validates required fields on product creation', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;

    $this
        ->withToken($token)
        ->postJson('/api/products', [])
        ->assertUnprocessable();
});

it('validates unique sku', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;

    $product = Product::factory()->create(['sku' => 'SKU-DUPE']);

    $this
        ->withToken($token)
        ->postJson('/api/products', [
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'name' => 'Another Product',
            'sku' => 'SKU-DUPE',
            'unit' => 'pcs',
        ])
        ->assertUnprocessable();
});

it('forbids member from managing products', function () {
    $member = User::factory()->create(['role' => 'member']);
    $token = $member->createToken('auth-token')->plainTextToken;

    $this
        ->withToken($token)
        ->getJson('/api/products')
        ->assertForbidden();
});
