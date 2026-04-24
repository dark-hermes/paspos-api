<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows main admin to fully manage users', function () {
    $mainStore = Store::factory()->create([
        'type' => 'main',
    ]);

    $branchStore = Store::factory()->create([
        'type' => 'branch',
    ]);

    $mainAdmin = User::factory()->create([
        'role' => 'main_admin',
        'store_id' => $mainStore->id,
    ]);

    $token = $mainAdmin->createToken('auth-token')->plainTextToken;

    $createResponse = $this
        ->withToken($token)
        ->postJson('/api/users', [
            'full_name' => 'Branch Admin One',
            'email' => 'branch.admin.one@example.com',
            'phone' => '081211110001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'branch_admin',
            'store_id' => $branchStore->id,
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.role', 'branch_admin')
        ->assertJsonPath('data.store_id', $branchStore->id);

    $userId = $createResponse->json('data.id');

    $this
        ->withToken($token)
        ->patchJson('/api/users/' . $userId, [
            'role' => 'cashier',
        ])
        ->assertOk()
        ->assertJsonPath('data.role', 'cashier');

    $this
        ->withToken($token)
        ->deleteJson('/api/users/' . $userId)
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect(User::query()->whereKey($userId)->exists())->toBeFalse();
});

it('allows branch admin to manage cashier and member in same store only', function () {
    $branchStore = Store::factory()->create([
        'type' => 'branch',
    ]);

    $anotherBranchStore = Store::factory()->create([
        'type' => 'branch',
    ]);

    $branchAdmin = User::factory()->create([
        'role' => 'branch_admin',
        'store_id' => $branchStore->id,
    ]);

    $token = $branchAdmin->createToken('auth-token')->plainTextToken;

    $createCashierResponse = $this
        ->withToken($token)
        ->postJson('/api/users', [
            'full_name' => 'Cashier A',
            'email' => 'cashier.a@example.com',
            'phone' => '081211110002',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'cashier',
        ]);

    $createCashierResponse
        ->assertCreated()
        ->assertJsonPath('data.role', 'cashier')
        ->assertJsonPath('data.store_id', $branchStore->id);

    $cashierId = $createCashierResponse->json('data.id');

    $member = User::factory()->create([
        'role' => 'member',
        'store_id' => $branchStore->id,
    ]);

    $this
        ->withToken($token)
        ->patchJson('/api/users/' . $member->id, [
            'full_name' => 'Member Updated',
        ])
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Member Updated')
        ->assertJsonPath('data.store_id', $branchStore->id);

    $this
        ->withToken($token)
        ->postJson('/api/users', [
            'full_name' => 'Wrong Store Cashier',
            'email' => 'wrong.store.cashier@example.com',
            'phone' => '081211110009',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'cashier',
            'store_id' => $anotherBranchStore->id,
        ])
        ->assertForbidden();

    $indexResponse = $this
        ->withToken($token)
        ->getJson('/api/users');

    $indexResponse
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $roles = collect($indexResponse->json('data'))->pluck('role')->unique()->values()->all();
    $storeIds = collect($indexResponse->json('data'))->pluck('store_id')->unique()->values()->all();

    expect($roles)->toEqualCanonicalizing(['branch_admin', 'cashier', 'member']);
    expect($storeIds)->toEqual([$branchStore->id]);

    $this
        ->withToken($token)
        ->deleteJson('/api/users/' . $cashierId)
        ->assertOk();
});

it('forbids branch admin from managing main admin roles', function () {
    $mainStore = Store::factory()->create([
        'type' => 'main',
    ]);

    $branchStore = Store::factory()->create([
        'type' => 'branch',
    ]);

    $branchAdmin = User::factory()->create([
        'role' => 'branch_admin',
        'store_id' => $branchStore->id,
    ]);

    $mainAdmin = User::factory()->create([
        'role' => 'main_admin',
        'store_id' => $mainStore->id,
    ]);

    $anotherBranchAdmin = User::factory()->create([
        'role' => 'branch_admin',
        'store_id' => $branchStore->id,
    ]);

    $token = $branchAdmin->createToken('auth-token')->plainTextToken;

    $this
        ->withToken($token)
        ->getJson('/api/users/' . $mainAdmin->id)
        ->assertForbidden();

    $this
        ->withToken($token)
        ->patchJson('/api/users/' . $anotherBranchAdmin->id, [
            'full_name' => 'Branch Admin Updated',
        ])
        ->assertOk();

    $this
        ->withToken($token)
        ->postJson('/api/users', [
            'full_name' => 'Branch Admin New',
            'email' => 'branch.admin.new@example.com',
            'phone' => '081211110003',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'branch_admin',
        ])
        ->assertCreated();
});

it('allows cashier to manage member in same store only', function () {
    $branchStore = Store::factory()->create([
        'type' => 'branch',
    ]);

    $cashier = User::factory()->create([
        'role' => 'cashier',
        'store_id' => $branchStore->id,
    ]);

    $token = $cashier->createToken('auth-token')->plainTextToken;

    $this
        ->withToken($token)
        ->postJson('/api/users', [
            'full_name' => 'Member By Cashier',
            'email' => 'member.by.cashier@example.com',
            'phone' => '081211110004',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'member',
        ])
        ->assertCreated();

    $this
        ->withToken($token)
        ->postJson('/api/users', [
            'full_name' => 'Cashier By Cashier',
            'email' => 'cashier.by.cashier@example.com',
            'phone' => '081211110005',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'cashier',
        ])
        ->assertForbidden();

    $indexResponse = $this
        ->withToken($token)
        ->getJson('/api/users');
        
    $indexResponse->assertOk();

    $roles = collect($indexResponse->json('data'))->pluck('role')->unique()->values()->all();
    expect($roles)->toEqualCanonicalizing(['member']);
});
