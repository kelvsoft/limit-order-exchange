<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;

// This ensures only logged-in users can access these endpoints
Route::middleware('auth:sanctum')->group(function () {
    
    // Get user balances and assets
    Route::get('/profile', [ProfileController::class, 'index']);
    
    // Order management
    Route::get('/orders', [OrderController::class, 'index']);
    
    // Order Book (For the public list of buy/sells)
    Route::get('/order-book', [OrderController::class, 'orderBook']);
});