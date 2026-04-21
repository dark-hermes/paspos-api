<?php

use App\Jobs\SendWhatsappOtpJob;
use App\Models\PhoneVerificationToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('registers user and dispatches otp job', function () {
    Queue::fake();

    $response = $this->postJson('/api/register', [
        'full_name' => 'Hermas Test',
        'phone' => '0812-3456-7890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('status', 'success');

    expect(User::query()->where('phone', '6281234567890')->exists())->toBeTrue();

    expect(
        PhoneVerificationToken::query()
            ->where('phone', '6281234567890')
            ->where('purpose', 'registration')
            ->exists()
    )->toBeTrue();

    Queue::assertPushed(SendWhatsappOtpJob::class, function (SendWhatsappOtpJob $job): bool {
        return $job->phone === '6281234567890' && $job->purpose === 'registration';
    });
});

it('verifies registration otp and returns token', function () {
    $user = User::factory()->create([
        'phone' => '628111111111',
        'phone_verified_at' => null,
        'email' => '628111111111@paspos.local',
    ]);

    PhoneVerificationToken::query()->create([
        'phone' => $user->phone,
        'purpose' => 'registration',
        'token' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = $this->postJson('/api/verify-otp', [
        'phone' => '08111111111',
        'otp' => '123456',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['status', 'token']);

    $user->refresh();

    expect($user->phone_verified_at)->not->toBeNull();
    expect(
        PhoneVerificationToken::query()
            ->where('phone', $user->phone)
            ->where('purpose', 'registration')
            ->exists()
    )->toBeFalse();
});

it('prevents login for unverified phone', function () {
    User::factory()->create([
        'phone' => '628222222222',
        'phone_verified_at' => null,
        'password' => 'password123',
        'email' => '628222222222@paspos.local',
    ]);

    $response = $this->postJson('/api/login', [
        'phone' => '628222222222',
        'password' => 'password123',
    ]);

    $response->assertForbidden();
});

it('sends reset otp and resets password using otp', function () {
    Queue::fake();

    $user = User::factory()->create([
        'phone' => '628333333333',
        'phone_verified_at' => now(),
        'password' => 'oldpassword123',
        'email' => '628333333333@paspos.local',
    ]);

    $forgotResponse = $this->postJson('/api/forgot-password', [
        'phone' => '628333333333',
    ]);

    $forgotResponse->assertOk();

    Queue::assertPushed(SendWhatsappOtpJob::class, function (SendWhatsappOtpJob $job): bool {
        return $job->phone === '628333333333' && $job->purpose === 'password_reset';
    });

    PhoneVerificationToken::query()->where('phone', $user->phone)->where('purpose', 'password_reset')->delete();

    PhoneVerificationToken::query()->create([
        'phone' => $user->phone,
        'purpose' => 'password_reset',
        'token' => Hash::make('654321'),
        'expires_at' => now()->addMinutes(5),
    ]);

    $resetResponse = $this->postJson('/api/reset-password', [
        'phone' => '628333333333',
        'otp' => '654321',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $resetResponse->assertOk()
        ->assertJsonPath('status', 'success');

    $user->refresh();

    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
});

it('rate limits otp requests per phone number', function () {
    Queue::fake();
    Cache::flush();

    config()->set('services.whatsapp.otp_rate_limit_max_attempts', 1);
    config()->set('services.whatsapp.otp_rate_limit_decay_seconds', 120);

    User::factory()->create([
        'phone' => '628444444444',
        'phone_verified_at' => now(),
        'email' => '628444444444@paspos.local',
    ]);

    $firstResponse = $this->postJson('/api/forgot-password', [
        'phone' => '628444444444',
    ]);

    $firstResponse->assertOk();

    $secondResponse = $this->postJson('/api/forgot-password', [
        'phone' => '628444444444',
    ]);

    $secondResponse->assertStatus(429)
        ->assertJsonPath('status', 'error')
        ->assertJsonStructure(['retry_after_seconds']);
});

it('logs out and revokes current sanctum token', function () {
    $user = User::factory()->create([
        'phone' => '628555555555',
        'phone_verified_at' => now(),
        'email' => '628555555555@paspos.local',
    ]);

    $plainTextToken = $user->createToken('auth-token')->plainTextToken;

    $logoutResponse = $this
        ->withToken($plainTextToken)
        ->postJson('/api/logout');

    $logoutResponse->assertOk()
        ->assertJsonPath('status', 'success');

    expect($user->tokens()->count())->toBe(0);
});

it('returns authenticated user profile from get me endpoint', function () {
    $user = User::factory()->create([
        'name' => 'Hermas Test',
        'phone' => '628666666666',
        'phone_verified_at' => now(),
        'email' => '628666666666@paspos.local',
    ]);

    $plainTextToken = $user->createToken('auth-token')->plainTextToken;

    $response = $this
        ->withToken($plainTextToken)
        ->getJson('/api/me');

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.full_name', $user->name)
        ->assertJsonPath('data.phone', $user->phone)
        ->assertJsonPath('data.email', $user->email);
});

it('updates authenticated user profile', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'phone' => '628777777777',
        'phone_verified_at' => now(),
        'email' => '628777777777@paspos.local',
    ]);

    $plainTextToken = $user->createToken('auth-token')->plainTextToken;

    $response = $this
        ->withToken($plainTextToken)
        ->patchJson('/api/me', [
            'full_name' => 'Updated Name',
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Profile updated successfully.')
        ->assertJsonPath('data.full_name', 'Updated Name');

    expect($user->fresh()->name)->toBe('Updated Name');
});

it('validates update profile payload', function () {
    $user = User::factory()->create([
        'phone' => '628888888888',
        'phone_verified_at' => now(),
        'email' => '628888888888@paspos.local',
    ]);

    $plainTextToken = $user->createToken('auth-token')->plainTextToken;

    $response = $this
        ->withToken($plainTextToken)
        ->patchJson('/api/me', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['full_name']);
});

it('updates authenticated user avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'name' => 'Avatar User',
        'phone' => '628999000111',
        'phone_verified_at' => now(),
        'email' => '628999000111@paspos.local',
    ]);

    $plainTextToken = $user->createToken('auth-token')->plainTextToken;
    $avatar = UploadedFile::fake()->image('avatar.jpg');

    $response = $this
        ->withToken($plainTextToken)
        ->patch('/api/me', [
            'avatar' => $avatar,
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'success');

    $updatedUser = $user->fresh();

    expect($updatedUser->avatar_path)->not->toBeNull();
    expect($response->json('data.avatar_url'))->not->toBeNull();

    Storage::disk('public')->assertExists((string) $updatedUser->avatar_path);
});

it('requests otp for authenticated phone update', function () {
    Queue::fake();

    $user = User::factory()->create([
        'phone' => '628999000200',
        'phone_verified_at' => now(),
        'email' => '628999000200@paspos.local',
    ]);

    $plainTextToken = $user->createToken('auth-token')->plainTextToken;

    $response = $this
        ->withToken($plainTextToken)
        ->postJson('/api/me/phone/request-otp', [
            'new_phone' => '08999000201',
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'success');

    expect(
        PhoneVerificationToken::query()
            ->where('phone', '628999000201')
            ->where('purpose', 'phone_update')
            ->exists()
    )->toBeTrue();

    Queue::assertPushed(SendWhatsappOtpJob::class, function (SendWhatsappOtpJob $job): bool {
        return $job->phone === '628999000201' && $job->purpose === 'phone_update';
    });
});

it('verifies otp and updates authenticated user phone number', function () {
    $user = User::factory()->create([
        'phone' => '628999000300',
        'phone_verified_at' => now(),
        'email' => '628999000300@paspos.local',
    ]);

    PhoneVerificationToken::query()->create([
        'phone' => '628999000301',
        'purpose' => 'phone_update',
        'token' => Hash::make('112233'),
        'expires_at' => now()->addMinutes(5),
    ]);

    $plainTextToken = $user->createToken('auth-token')->plainTextToken;

    $response = $this
        ->withToken($plainTextToken)
        ->postJson('/api/me/phone/verify-otp', [
            'new_phone' => '08999000301',
            'otp' => '112233',
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.phone', '628999000301');

    $updatedUser = $user->fresh();

    expect($updatedUser->phone)->toBe('628999000301');
    expect($updatedUser->email)->toBe('628999000301@paspos.local');
    expect(
        PhoneVerificationToken::query()
            ->where('phone', '628999000301')
            ->where('purpose', 'phone_update')
            ->exists()
    )->toBeFalse();
});

it('updates authenticated user password', function () {
    $user = User::factory()->create([
        'phone' => '628999000400',
        'phone_verified_at' => now(),
        'password' => 'oldpassword123',
        'email' => '628999000400@paspos.local',
    ]);

    $plainTextToken = $user->createToken('auth-token')->plainTextToken;

    $response = $this
        ->withToken($plainTextToken)
        ->putJson('/api/me/password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'success');

    expect(Hash::check('newpassword123', (string) $user->fresh()->password))->toBeTrue();
});

it('rejects authenticated user password update with invalid current password', function () {
    $user = User::factory()->create([
        'phone' => '628999000500',
        'phone_verified_at' => now(),
        'password' => 'oldpassword123',
        'email' => '628999000500@paspos.local',
    ]);

    $plainTextToken = $user->createToken('auth-token')->plainTextToken;

    $response = $this
        ->withToken($plainTextToken)
        ->putJson('/api/me/password', [
            'current_password' => 'wrong-password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'Current password is invalid.');
});
