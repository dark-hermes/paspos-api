<?php

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to create IN stock movement and auto-updates inventory', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $dest = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $dest->id, 'product_id' => $product->id, 'stock' => 10]);

    $createResponse = $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => null,
        'dest_store_id' => $dest->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'type' => 'in',
        'title' => 'Barang Masuk',
    ]);

    $createResponse->assertCreated()->assertJsonPath('status', 'success');
    
    $destInv = Inventory::where('store_id', $dest->id)->where('product_id', $product->id)->first();
    expect((float) $destInv->stock)->toBe(30.0);
});

it('allows admin to create OUT stock movement and auto-updates inventory', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $src = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $src->id, 'product_id' => $product->id, 'stock' => 100]);

    $createResponse = $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => $src->id,
        'dest_store_id' => null,
        'product_id' => $product->id,
        'quantity' => 15,
        'type' => 'out',
        'title' => 'Barang Keluar',
    ]);

    $createResponse->assertCreated()->assertJsonPath('status', 'success');
    
    $srcInv = Inventory::where('store_id', $src->id)->where('product_id', $product->id)->first();
    expect((float) $srcInv->stock)->toBe(85.0);
});

it('allows admin to create TRANSFER stock movement and auto-updates inventory', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $src = Store::factory()->create();
    $dest = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $src->id, 'product_id' => $product->id, 'stock' => 100]);
    Inventory::factory()->create(['store_id' => $dest->id, 'product_id' => $product->id, 'stock' => 10]);

    $createResponse = $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => $src->id,
        'dest_store_id' => $dest->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'type' => 'transfer',
        'title' => 'Transfer internal',
    ]);

    $createResponse->assertCreated()->assertJsonPath('status', 'success');
    
    $srcInv = Inventory::where('store_id', $src->id)->where('product_id', $product->id)->first();
    $destInv = Inventory::where('store_id', $dest->id)->where('product_id', $product->id)->first();
    expect((float) $srcInv->stock)->toBe(80.0);
    expect((float) $destInv->stock)->toBe(30.0);
});

it('fails with 422 on insufficient stock during out movement', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $src = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $src->id, 'product_id' => $product->id, 'stock' => 5]);

    $response = $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => $src->id,
        'dest_store_id' => null,
        'product_id' => $product->id,
        'quantity' => 10,
        'type' => 'out',
        'title' => 'Barang Keluar (Insufficient)',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonFragment(['message' => "Insufficient stock for this operation. Store ID {$src->id} does not have enough stock for Product ID {$product->id}."]);
        
    $srcInv = Inventory::where('store_id', $src->id)->where('product_id', $product->id)->first();
    expect((float) $srcInv->stock)->toBe(5.0); // Stock shouldn't be deducted
});

it('fails if rules are violated (e.g. IN with src_store_id provided)', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $store = Store::factory()->create();
    $product = Product::factory()->create();

    // 500 error expected because InvalidArgumentException is thrown
    $response = $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => $store->id,
        'dest_store_id' => $store->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'type' => 'in',
        'title' => 'Wrong format IN',
    ]);
    
    $response->assertStatus(500);
});

it('reverses inventory on stock movement deletion', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $dest = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $dest->id, 'product_id' => $product->id, 'stock' => 10]);

    $createResponse = $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => null,
        'dest_store_id' => $dest->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'type' => 'in',
        'title' => 'Masuk',
    ]);

    $movementId = $createResponse->json('data.id');

    $this->withToken($token)->deleteJson("/api/stock-movements/$movementId")->assertOk();

    $destInv = Inventory::where('store_id', $dest->id)->where('product_id', $product->id)->first();
    expect((float) $destInv->stock)->toBe(10.0); // Reverted back
});

it('allows admin to list and filter stock movements', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $src = Store::factory()->create();
    $dest = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $src->id, 'product_id' => $product->id, 'stock' => 200]);
    Inventory::factory()->create(['store_id' => $dest->id, 'product_id' => $product->id, 'stock' => 10]);

    $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => null, 'dest_store_id' => $dest->id,
        'product_id' => $product->id, 'quantity' => 10, 'type' => 'in', 'title' => 'M1',
    ])->assertCreated();

    $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => $src->id, 'dest_store_id' => null,
        'product_id' => $product->id, 'quantity' => 5, 'type' => 'out', 'title' => 'M2',
    ])->assertCreated();

    $r = $this->withToken($token)->getJson('/api/stock-movements?type=in');
    $r->assertOk();
    expect($r->json('data'))->toHaveCount(1);
});

it('only allows updating title and note on stock movement', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;
    $dest = Store::factory()->create();
    $product = Product::factory()->create();

    Inventory::factory()->create(['store_id' => $dest->id, 'product_id' => $product->id, 'stock' => 50]);

    $createResponse = $this->withToken($token)->postJson('/api/stock-movements', [
        'src_store_id' => null, 'dest_store_id' => $dest->id,
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
