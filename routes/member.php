<?php

use App\Http\Controllers\Member\AddressController;
use App\Http\Controllers\Member\BranchController;
use App\Http\Controllers\Member\CartController;
use App\Http\Controllers\Member\CatalogController;
use App\Http\Controllers\Member\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('branches', [BranchController::class, 'index']);

// PUBLIC: Catalog browsing without authentication
Route::prefix('{branch}')->group(function (): void {
    Route::prefix('catalog')->name('catalog.')->group(function (): void {
        Route::get('products', [CatalogController::class, 'products']);
        Route::get('products/{product}', [CatalogController::class, 'show']);
        Route::get('categories', [CatalogController::class, 'categories']);
        Route::get('brands', [CatalogController::class, 'brands']);
    });
});

// PRIVATE: Requires authentication
Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('addresses', AddressController::class);
    Route::apiResource('transactions', TransactionController::class)->only(['index', 'show']);

    Route::prefix('{branch}')->group(function (): void {
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart', [CartController::class, 'store']);
        Route::delete('cart/{cartItem}', [CartController::class, 'destroy']);
        Route::post('cart/checkout', [CartController::class, 'checkout']);
    });
});
