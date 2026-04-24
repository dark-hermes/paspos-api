<?php

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to perform product category crud', function () {
    $admin = User::factory()->create([
        'role' => 'main_admin',
    ]);

    $token = $admin->createToken('auth-token')->plainTextToken;

    // Create
    $createResponse = $this
        ->withToken($token)
        ->postJson('/api/product-categories', [
            'name' => 'Makanan',
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Makanan');

    $categoryId = $createResponse->json('data.id');

    // Index
    $this
        ->withToken($token)
        ->getJson('/api/product-categories')
        ->assertOk()
        ->assertJsonPath('status', 'success');

    // Show
    $this
        ->withToken($token)
        ->getJson('/api/product-categories/' . $categoryId)
        ->assertOk()
        ->assertJsonPath('data.id', $categoryId)
        ->assertJsonPath('data.name', 'Makanan');

    // Update
    $this
        ->withToken($token)
        ->patchJson('/api/product-categories/' . $categoryId, [
            'name' => 'Minuman',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Minuman');

    // Delete
    $this
        ->withToken($token)
        ->deleteJson('/api/product-categories/' . $categoryId)
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect(ProductCategory::query()->whereKey($categoryId)->exists())->toBeFalse();
});

it('allows branch admin to manage product categories', function () {
    $branchAdmin = User::factory()->create([
        'role' => 'branch_admin',
    ]);

    $token = $branchAdmin->createToken('auth-token')->plainTextToken;

    $this
        ->withToken($token)
        ->postJson('/api/product-categories', ['name' => 'Snack'])
        ->assertCreated();

    $this
        ->withToken($token)
        ->getJson('/api/product-categories')
        ->assertOk();
});

it('forbids member from managing product categories', function () {
    $member = User::factory()->create([
        'role' => 'member',
    ]);

    $token = $member->createToken('auth-token')->plainTextToken;

    $this
        ->withToken($token)
        ->getJson('/api/product-categories')
        ->assertForbidden();

    $this
        ->withToken($token)
        ->postJson('/api/product-categories', ['name' => 'Snack'])
        ->assertForbidden();
});

it('validates unique category name', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;

    ProductCategory::factory()->create(['name' => 'Makanan']);

    $this
        ->withToken($token)
        ->postJson('/api/product-categories', ['name' => 'Makanan'])
        ->assertUnprocessable();
});

it('searches product categories by name', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;

    ProductCategory::factory()->create(['name' => 'Makanan']);
    ProductCategory::factory()->create(['name' => 'Minuman']);

    $response = $this
        ->withToken($token)
        ->getJson('/api/product-categories?search=Makan');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.name'))->toBe('Makanan');
});
