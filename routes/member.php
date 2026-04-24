<?php

use App\Http\Controllers\Member\AddressController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('addresses', AddressController::class);
});
