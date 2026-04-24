<?php

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to create stock movement and auto-updates inventory', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $src = Store::factory()->create();
    $dest = Store::factory()->create();
    $product = Product::factory()->create();

    // Create inventory for source store with initial stock
    Inventory::factory()->create(['store_id' => $src->id, 'product_id' => $product->id, 'stock' => 100]);
    Inventory::factory()->create(['store_id' => $dest->id, 'product_id' => $product->id, 'stock' => 10]);

    $createResponse = $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => $src->id, 'dest_store_id' => $dest->id,
        'product_id' => $product->id, 'quantity' => 20, 'type' => 'in',
        'title' => 'Transfer masuk',
    ]);

    $createResponse->assertCreated()->assertJsonPath('status', 'success')
        ->assertJsonPath('data.type', 'in');
    expect($createResponse->json('data.quantity'))->toEqual(20);

    // Verify inventory was adjusted: src decreased, dest increased
    $srcInv = Inventory::where('store_id', $src->id)->where('product_id', $product->id)->first();
    $destInv = Inventory::where('store_id', $dest->id)->where('product_id', $product->id)->first();
    expect((float) $srcInv->stock)->toBe(80.0);
    expect((float) $destInv->stock)->toBe(30.0);
});

it('reverses inventory on stock movement deletion', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $src = Store::factory()->create();
    $dest = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $src->id, 'product_id' => $product->id, 'stock' => 100]);
    Inventory::factory()->create(['store_id' => $dest->id, 'product_id' => $product->id, 'stock' => 10]);

    $createResponse = $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => $src->id, 'dest_store_id' => $dest->id,
        'product_id' => $product->id, 'quantity' => 20, 'type' => 'in',
        'title' => 'Transfer',
    ]);

    $movementId = $createResponse->json('data.id');

    // Delete and verify reversal
    $this->withToken($token)->deleteJson("/api/stock-movements/$movementId")->assertOk();

    $srcInv = Inventory::where('store_id', $src->id)->where('product_id', $product->id)->first();
    $destInv = Inventory::where('store_id', $dest->id)->where('product_id', $product->id)->first();
    expect((float) $srcInv->stock)->toBe(100.0);
    expect((float) $destInv->stock)->toBe(10.0);
});

it('allows admin to list and filter stock movements', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $src = Store::factory()->create();
    $dest = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $src->id, 'product_id' => $product->id, 'stock' => 200]);
    Inventory::factory()->create(['store_id' => $dest->id, 'product_id' => $product->id, 'stock' => 0]);

    $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => $src->id, 'dest_store_id' => $dest->id,
        'product_id' => $product->id, 'quantity' => 10, 'type' => 'in', 'title' => 'M1',
    ])->assertCreated();

    $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => $src->id, 'dest_store_id' => $dest->id,
        'product_id' => $product->id, 'quantity' => 5, 'type' => 'out', 'title' => 'M2',
    ])->assertCreated();

    // Filter by type
    $r = $this->withToken($token)->getJson('/api/stock-movements?type=in');
    $r->assertOk();
    expect($r->json('data'))->toHaveCount(1);
});

it('only allows updating title and note on stock movement', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $src = Store::factory()->create();
    $dest = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $src->id, 'product_id' => $product->id, 'stock' => 50]);

    $createResponse = $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => $src->id, 'dest_store_id' => $dest->id,
        'product_id' => $product->id, 'quantity' => 10, 'type' => 'in', 'title' => 'Old',
    ]);

    $id = $createResponse->json('data.id');

    $this->withToken($token)->patchJson("/api/stock-movements/$id", [
        'title' => 'New Title', 'note' => 'Added note',
    ])->assertOk()->assertJsonPath('data.title', 'New Title')
      ->assertJsonPath('data.note', 'Added note');
});

it('forbids member from managing stock movements', function () {
    $member = User::factory()->create(['role' => 'member']);
    $token = $member->createToken('auth-token')->plainTextToken;
    $this->withToken($token)->getJson('/api/stock-movements')->assertForbidden();
});
