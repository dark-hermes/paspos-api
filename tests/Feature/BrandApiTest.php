<?php

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to perform brand crud', function () {
    $admin = User::factory()->create([
        'role' => 'main_admin',
    ]);

    $token = $admin->createToken('auth-token')->plainTextToken;

    // Create
    $createResponse = $this
        ->withToken($token)
        ->postJson('/api/brands', [
            'name' => 'Indofood',
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Indofood');

    $brandId = $createResponse->json('data.id');

    // Index
    $this
        ->withToken($token)
        ->getJson('/api/brands')
        ->assertOk()
        ->assertJsonPath('status', 'success');

    // Show
    $this
        ->withToken($token)
        ->getJson('/api/brands/' . $brandId)
        ->assertOk()
        ->assertJsonPath('data.id', $brandId)
        ->assertJsonPath('data.name', 'Indofood');

    // Update
    $this
        ->withToken($token)
        ->patchJson('/api/brands/' . $brandId, [
            'name' => 'Indofood Updated',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Indofood Updated');

    // Delete
    $this
        ->withToken($token)
        ->deleteJson('/api/brands/' . $brandId)
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect(Brand::query()->whereKey($brandId)->exists())->toBeFalse();
});

it('allows branch admin to manage brands', function () {
    $branchAdmin = User::factory()->create([
        'role' => 'branch_admin',
    ]);

    $token = $branchAdmin->createToken('auth-token')->plainTextToken;

    $this
        ->withToken($token)
        ->postJson('/api/brands', ['name' => 'Unilever'])
        ->assertCreated();

    $this
        ->withToken($token)
        ->getJson('/api/brands')
        ->assertOk();
});

it('forbids member from managing brands', function () {
    $member = User::factory()->create([
        'role' => 'member',
    ]);

    $token = $member->createToken('auth-token')->plainTextToken;

    $this
        ->withToken($token)
        ->getJson('/api/brands')
        ->assertForbidden();
});

it('validates unique brand name', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;

    Brand::factory()->create(['name' => 'Indofood']);

    $this
        ->withToken($token)
        ->postJson('/api/brands', ['name' => 'Indofood'])
        ->assertUnprocessable();
});

it('searches brands by name', function () {
    $admin = User::factory()->create(['role' => 'main_admin']);
    $token = $admin->createToken('auth-token')->plainTextToken;

    Brand::factory()->create(['name' => 'Indofood']);
    Brand::factory()->create(['name' => 'Unilever']);

    $response = $this
        ->withToken($token)
        ->getJson('/api/brands?search=Indo');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.name'))->toBe('Indofood');
});
