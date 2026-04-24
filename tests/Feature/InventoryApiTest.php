<?php

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to perform inventory crud', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $store = Store::factory()->create();
    $product = Product::factory()->create();

    $createResponse = $this->withToken($token)->postJson('/api/inventories', [
        'store_id' => $store->id, 'product_id' => $product->id,
        'stock' => 100, 'purchase_price' => 5000, 'selling_price' => 7500,
        'discount_percentage' => 10, 'min_stock' => 5,
    ]);

    $createResponse->assertCreated()->assertJsonPath('status', 'success');
    expect($createResponse->json('data.stock'))->toEqual(100);

    $id = $createResponse->json('data.id');

    $this->withToken($token)->getJson('/api/inventories')->assertOk();
    $this->withToken($token)->getJson("/api/inventories/$id")->assertOk();
    $updateResponse = $this->withToken($token)->patchJson("/api/inventories/$id", ['selling_price' => 8000]);
    $updateResponse->assertOk();
    expect($updateResponse->json('data.selling_price'))->toEqual(8000);
    $this->withToken($token)->deleteJson("/api/inventories/$id")->assertOk();
    expect(Inventory::query()->whereKey($id)->exists())->toBeFalse();
});

it('prevents duplicate store-product inventory', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $store = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $store->id, 'product_id' => $product->id]);

    $this->withToken($token)->postJson('/api/inventories', [
        'store_id' => $store->id, 'product_id' => $product->id,
        'purchase_price' => 5000, 'selling_price' => 7500,
    ])->assertStatus(422);
});

it('filters inventories by store_id', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $s1 = Store::factory()->create();
    $s2 = Store::factory()->create();
    $p = Product::factory()->create();
    Inventory::factory()->create(['store_id' => $s1->id, 'product_id' => $p->id]);
    Inventory::factory()->create(['store_id' => $s2->id, 'product_id' => $p->id]);

    $r = $this->withToken($token)->getJson("/api/inventories?store_id=$s1->id");
    $r->assertOk();
    expect($r->json('data'))->toHaveCount(1);
});

it('filters low stock inventories', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    Inventory::factory()->create(['stock' => 2, 'min_stock' => 10]);
    Inventory::factory()->create(['stock' => 100, 'min_stock' => 10]);

    $r = $this->withToken($token)->getJson('/api/inventories?low_stock=true');
    $r->assertOk();
    expect($r->json('data'))->toHaveCount(1);
});

it('forbids member from managing inventories', function () {
    $member = User::factory()->create(['role' => 'member']);
    $token = $member->createToken('auth-token')->plainTextToken;
    $this->withToken($token)->getJson('/api/inventories')->assertForbidden();
});
