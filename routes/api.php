<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/admin/forgot-password', [AuthController::class, 'forgotPasswordAdmin']);
Route::post('/admin/reset-password', [AuthController::class, 'resetPasswordAdmin']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me', [AuthController::class, 'updateProfile']);
    Route::put('/me/password', [AuthController::class, 'updatePassword']);
    Route::post('/me/phone/request-otp', [AuthController::class, 'requestPhoneUpdateOtp']);
    Route::post('/me/phone/verify-otp', [AuthController::class, 'verifyPhoneUpdateOtp']);
    Route::post('/me/email/request-otp', [AuthController::class, 'requestEmailUpdateOtp']);
    Route::post('/me/email/verify-otp', [AuthController::class, 'verifyEmailUpdateOtp']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::apiResource('stores', StoreController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('inventories', InventoryController::class);
    Route::apiResource('stock-movements', StockMovementController::class);
});

Route::prefix('member')->name('member.')->group(base_path('routes/member.php'));
