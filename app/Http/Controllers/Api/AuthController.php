<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Jobs\SendWhatsappOtpJob;
use App\Models\PhoneVerificationToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $rateLimitResponse = $this->ensureOtpRateLimit($data['phone'], 'registration');

        if ($rateLimitResponse) {
            return $rateLimitResponse;
        }

        $user = User::query()->create([
            'name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['phone'] . '@paspos.local',
            'password' => $data['password'],
            'phone_verified_at' => null,
        ]);

        $otp = $this->createOtp($user->phone, 'registration');

        SendWhatsappOtpJob::dispatch($user->phone, $otp, 'registration');

        return response()->json([
            'status' => 'success',
            'message' => 'OTP sent to WhatsApp.',
        ], 201);
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->where('phone', $data['phone'])->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->phone_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Phone number is already verified.',
            ], 422);
        }

        $rateLimitResponse = $this->ensureOtpRateLimit($data['phone'], 'registration');

        if ($rateLimitResponse) {
            return $rateLimitResponse;
        }

        $otp = $this->createOtp($data['phone'], 'registration');

        SendWhatsappOtpJob::dispatch($data['phone'], $otp, 'registration');

        return response()->json([
            'status' => 'success',
            'message' => 'OTP resent to WhatsApp.',
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->where('phone', $data['phone'])->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        if (! $this->isValidOtp($data['phone'], 'registration', $data['otp'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $user->forceFill([
            'phone_verified_at' => now(),
        ])->save();

        PhoneVerificationToken::query()
            ->where('phone', $data['phone'])
            ->where('purpose', 'registration')
            ->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->where('phone', $data['phone'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials.',
            ], 422);
        }

        if (! $user->phone_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Phone number is not verified.',
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $rateLimitResponse = $this->ensureOtpRateLimit($data['phone'], 'password_reset');

        if ($rateLimitResponse) {
            return $rateLimitResponse;
        }

        $otp = $this->createOtp($data['phone'], 'password_reset');

        SendWhatsappOtpJob::dispatch($data['phone'], $otp, 'password_reset');

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset OTP sent to WhatsApp.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->where('phone', $data['phone'])->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        if (! $this->isValidOtp($data['phone'], 'password_reset', $data['otp'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $user->forceFill([
            'password' => $data['password'],
        ])->save();

        $user->tokens()->delete();

        PhoneVerificationToken::query()
            ->where('phone', $data['phone'])
            ->where('purpose', 'password_reset')
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password has been reset.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }

    private function createOtp(string $phone, string $purpose): string
    {
        $otp = (string) random_int(100000, 999999);

        PhoneVerificationToken::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->delete();

        PhoneVerificationToken::query()->create([
            'phone' => $phone,
            'purpose' => $purpose,
            'token' => Hash::make($otp),
            'expires_at' => now()->addMinutes((int) config('services.whatsapp.otp_expires_in_minutes', 5)),
        ]);

        return $otp;
    }

    private function isValidOtp(string $phone, string $purpose, string $otp): bool
    {
        $verification = PhoneVerificationToken::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $verification) {
            return false;
        }

        return Hash::check($otp, $verification->token);
    }

    private function ensureOtpRateLimit(string $phone, string $purpose): ?JsonResponse
    {
        $maxAttempts = (int) config('services.whatsapp.otp_rate_limit_max_attempts', 1);
        $decaySeconds = (int) config('services.whatsapp.otp_rate_limit_decay_seconds', 60);
        $rateLimitKey = 'otp:' . $purpose . ':' . $phone;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'status' => 'error',
                'message' => 'Too many OTP requests for this phone number.',
                'retry_after_seconds' => $retryAfter,
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, $decaySeconds);

        return null;
    }
}
