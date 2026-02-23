<?php

use App\Jobs\SendWhatsappOtpJob;
use App\Models\PhoneVerificationToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

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
