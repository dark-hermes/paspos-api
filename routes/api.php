<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me', [AuthController::class, 'updateProfile']);
    Route::put('/me/password', [AuthController::class, 'updatePassword']);
    Route::post('/me/phone/request-otp', [AuthController::class, 'requestPhoneUpdateOtp']);
    Route::post('/me/phone/verify-otp', [AuthController::class, 'verifyPhoneUpdateOtp']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::apiResource('stores', StoreController::class);
    Route::apiResource('users', UserController::class);
});
