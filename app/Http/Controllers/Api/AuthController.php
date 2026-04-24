<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordAdminRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RequestEmailUpdateOtpRequest;
use App\Http\Requests\RequestPhoneUpdateOtpRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\ResetPasswordAdminRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\VerifyEmailUpdateOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\VerifyPhoneUpdateOtpRequest;
use App\Http\Resources\AuthUserResource;
use App\Jobs\SendEmailOtpJob;
use App\Jobs\SendWhatsappOtpJob;
use App\Models\EmailVerificationToken;
use App\Models\PhoneVerificationToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

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
            'password' => $data['password'],
            'phone_verified_at' => null,
            'role' => 'member',
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

        $user = null;
        $isEmailLogin = array_key_exists('email', $data) && ! empty($data['email']);

        if ($isEmailLogin) {
            $user = User::query()->where('email', $data['email'])->first();
        } else {
            $user = User::query()->where('phone', $data['phone'])->first();
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials.',
            ], 422);
        }

        if ($user->role !== 'member' && ! $isEmailLogin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin harus login menggunakan email.',
            ], 403);
        }

        if ($user->role === 'member' && ! $isEmailLogin && ! $user->phone_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Phone number is not verified.',
            ], 403);
        }

        if ($user->role === 'member' && $isEmailLogin && ! $user->email_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email address is not verified.',
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

    public function forgotPasswordAdmin(ForgotPasswordAdminRequest $request): JsonResponse
    {
        $data = $request->validated();

        $rateLimitResponse = $this->ensureEmailOtpRateLimit($data['email'], 'password_reset');

        if ($rateLimitResponse) {
            return $rateLimitResponse;
        }

        $otp = $this->createEmailOtp($data['email'], 'password_reset');

        SendEmailOtpJob::dispatch($data['email'], $otp, 'password_reset');

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset OTP sent to email.',
        ]);
    }

    public function resetPasswordAdmin(ResetPasswordAdminRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        if (! $this->isValidEmailOtp($data['email'], 'password_reset', $data['otp'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $user->forceFill([
            'password' => $data['password'],
        ])->save();

        $user->tokens()->delete();

        EmailVerificationToken::query()
            ->where('email', $data['email'])
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

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => new AuthUserResource($request->user()),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->user()->getAuthIdentifier());
        $data = $request->validated();
        $attributes = [];

        if (array_key_exists('full_name', $data) && is_string($data['full_name'])) {
            $attributes['name'] = $data['full_name'];
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $attributes['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        if ($attributes !== []) {
            $user->forceFill($attributes)->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'data' => new AuthUserResource($user->fresh()),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->user()->getAuthIdentifier());
        $data = $request->validated();

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is invalid.',
            ], 422);
        }

        $user->forceFill([
            'password' => $data['new_password'],
        ])->save();

        $currentAccessTokenId = $request->user()?->currentAccessToken()?->id;

        if ($currentAccessTokenId) {
            $user->tokens()->where('id', '!=', $currentAccessTokenId)->delete();
        } else {
            $user->tokens()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully.',
        ]);
    }

    public function requestPhoneUpdateOtp(RequestPhoneUpdateOtpRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->user()->getAuthIdentifier());
        $data = $request->validated();

        if ($data['new_phone'] === $user->phone) {
            return response()->json([
                'status' => 'error',
                'message' => 'New phone number must be different from current phone number.',
            ], 422);
        }

        $rateLimitResponse = $this->ensureOtpRateLimit($data['new_phone'], 'phone_update');

        if ($rateLimitResponse) {
            return $rateLimitResponse;
        }

        $otp = $this->createOtp($data['new_phone'], 'phone_update');

        SendWhatsappOtpJob::dispatch($data['new_phone'], $otp, 'phone_update');

        return response()->json([
            'status' => 'success',
            'message' => 'Phone update OTP sent to WhatsApp.',
        ]);
    }

    public function verifyPhoneUpdateOtp(VerifyPhoneUpdateOtpRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->user()->getAuthIdentifier());
        $data = $request->validated();

        if ($data['new_phone'] === $user->phone) {
            return response()->json([
                'status' => 'error',
                'message' => 'New phone number must be different from current phone number.',
            ], 422);
        }

        if (! $this->isValidOtp($data['new_phone'], 'phone_update', $data['otp'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $user->forceFill([
            'phone' => $data['new_phone'],
            'phone_verified_at' => now(),
        ])->save();

        PhoneVerificationToken::query()
            ->where('phone', $data['new_phone'])
            ->where('purpose', 'phone_update')
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Phone number updated successfully.',
            'data' => new AuthUserResource($user->fresh()),
        ]);
    }

    public function requestEmailUpdateOtp(RequestEmailUpdateOtpRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->user()->getAuthIdentifier());
        $data = $request->validated();

        if ($data['new_email'] === $user->email) {
            return response()->json([
                'status' => 'error',
                'message' => 'New email address must be different from current email address.',
            ], 422);
        }

        $rateLimitResponse = $this->ensureEmailOtpRateLimit($data['new_email'], 'email_update');

        if ($rateLimitResponse) {
            return $rateLimitResponse;
        }

        $otp = $this->createEmailOtp($data['new_email'], 'email_update');

        SendEmailOtpJob::dispatch($data['new_email'], $otp);

        return response()->json([
            'status' => 'success',
            'message' => 'Email update OTP sent to your new email address.',
        ]);
    }

    public function verifyEmailUpdateOtp(VerifyEmailUpdateOtpRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->user()->getAuthIdentifier());
        $data = $request->validated();

        if ($data['new_email'] === $user->email) {
            return response()->json([
                'status' => 'error',
                'message' => 'New email address must be different from current email address.',
            ], 422);
        }

        if (! $this->isValidEmailOtp($data['new_email'], 'email_update', $data['otp'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $user->forceFill([
            'email' => $data['new_email'],
            'email_verified_at' => now(),
        ])->save();

        EmailVerificationToken::query()
            ->where('email', $data['new_email'])
            ->where('purpose', 'email_update')
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Email address updated successfully.',
            'data' => new AuthUserResource($user->fresh()),
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
        $rateLimitKey = 'otp:'.$purpose.':'.$phone;

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

    private function createEmailOtp(string $email, string $purpose): string
    {
        $otp = (string) random_int(100000, 999999);

        EmailVerificationToken::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->delete();

        EmailVerificationToken::query()->create([
            'email' => $email,
            'purpose' => $purpose,
            'token' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5), // Email OTP valid for 5 mins
        ]);

        return $otp;
    }

    private function isValidEmailOtp(string $email, string $purpose, string $otp): bool
    {
        $verification = EmailVerificationToken::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $verification) {
            return false;
        }

        return Hash::check($otp, $verification->token);
    }

    private function ensureEmailOtpRateLimit(string $email, string $purpose): ?JsonResponse
    {
        $maxAttempts = 1; // Rate limit max attempts
        $decaySeconds = 60; // Rate limit decay seconds
        $rateLimitKey = 'email_otp:'.$purpose.':'.$email;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'status' => 'error',
                'message' => 'Too many OTP requests for this email address.',
                'retry_after_seconds' => $retryAfter,
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, $decaySeconds);

        return null;
    }
}
