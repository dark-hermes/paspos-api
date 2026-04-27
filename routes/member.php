<?php

use App\Http\Controllers\Member\AddressController;
use App\Http\Controllers\Member\CartController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('addresses', AddressController::class);
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart', [CartController::class, 'store']);
    Route::delete('cart/{cartItem}', [CartController::class, 'destroy']);
    Route::post('cart/checkout', [CartController::class, 'checkout']);
});
