<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates main admin user via artisan command', function () {
    $mainStore = Store::factory()->create([
        'type' => 'main',
    ]);

    $exitCode = Artisan::call('user:create-admin', [
        'role' => 'main_admin',
        '--name' => 'Main Admin CLI',
        '--email' => 'main-admin-cli@example.com',
        '--phone' => '0812-3456-7890',
        '--password' => 'password123',
        '--store_id' => (string) $mainStore->id,
    ]);

    expect($exitCode)->toBe(0);

    $createdUser = User::query()->where('email', 'main-admin-cli@example.com')->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser?->role)->toBe('main_admin');
    expect($createdUser?->phone)->toBe('6281234567890');
    expect($createdUser?->store_id)->toBe($mainStore->id);
    expect($createdUser?->phone_verified_at)->not->toBeNull();
    expect(Hash::check('password123', (string) $createdUser?->password))->toBeTrue();
});

it('creates branch admin user via artisan command', function () {
    $branchStore = Store::factory()->create([
        'type' => 'branch',
    ]);

    $exitCode = Artisan::call('user:create-admin', [
        'role' => 'branch_admin',
        '--name' => 'Branch Admin CLI',
        '--email' => 'branch-admin-cli@example.com',
        '--phone' => '0813-0000-0001',
        '--password' => 'password123',
        '--store_id' => (string) $branchStore->id,
    ]);

    expect($exitCode)->toBe(0);

    $createdUser = User::query()->where('email', 'branch-admin-cli@example.com')->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser?->role)->toBe('branch_admin');
    expect($createdUser?->store_id)->toBe($branchStore->id);
});

it('fails when role is invalid', function () {
    $exitCode = Artisan::call('user:create-admin', [
        'role' => 'super_admin',
        '--name' => 'Invalid Role User',
        '--email' => 'invalid-role@example.com',
        '--password' => 'password123',
    ]);

    expect($exitCode)->toBe(1);

    expect(User::query()->where('email', 'invalid-role@example.com')->exists())->toBeFalse();
});

it('fails when branch admin is created without store id', function () {
    $exitCode = Artisan::call('user:create-admin', [
        'role' => 'branch_admin',
        '--name' => 'Branch Without Store',
        '--email' => 'branch-without-store@example.com',
        '--password' => 'password123',
    ]);

    expect($exitCode)->toBe(1);

    expect(User::query()->where('email', 'branch-without-store@example.com')->exists())->toBeFalse();
});
