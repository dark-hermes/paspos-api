<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows main admin to perform store crud', function () {
    $mainAdmin = User::factory()->create([
        'role' => 'main_admin',
    ]);

    $token = $mainAdmin->createToken('auth-token')->plainTextToken;

    $createResponse = $this
        ->withToken($token)
        ->postJson('/api/stores', [
            'name' => 'Main Branch A',
            'address' => 'Jl. Utama 123',
            'type' => 'main',
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Main Branch A');

    $storeId = $createResponse->json('data.id');

    $this
        ->withToken($token)
        ->getJson('/api/stores')
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $this
        ->withToken($token)
        ->getJson('/api/stores/' . $storeId)
        ->assertOk()
        ->assertJsonPath('data.id', $storeId);

    $this
        ->withToken($token)
        ->patchJson('/api/stores/' . $storeId, [
            'name' => 'Main Branch B',
            'address' => 'Jl. Utama 456',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Main Branch B');

    $this
        ->withToken($token)
        ->deleteJson('/api/stores/' . $storeId)
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect(Store::query()->whereKey($storeId)->exists())->toBeFalse();
});

it('forbids branch admin from managing stores', function () {
    $branchAdmin = User::factory()->create([
        'role' => 'branch_admin',
    ]);

    $store = Store::factory()->create();

    $token = $branchAdmin->createToken('auth-token')->plainTextToken;

    $this
        ->withToken($token)
        ->getJson('/api/stores')
        ->assertForbidden();

    $this
        ->withToken($token)
        ->postJson('/api/stores', [
            'name' => 'Branch Store X',
            'type' => 'branch',
        ])
        ->assertForbidden();

    $this
        ->withToken($token)
        ->deleteJson('/api/stores/' . $store->id)
        ->assertForbidden();
});
