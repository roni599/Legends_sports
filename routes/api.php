<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles.permissions');
    });

    // Only Manager or Admin can manage Grounds and Pricing Rules
    Route::middleware('permission:manage_grounds')->group(function() {
        Route::apiResource('grounds', App\Http\Controllers\GroundController::class);
        Route::apiResource('pricing-rules', App\Http\Controllers\PricingRuleController::class);
    });
    
    // Cashier, Manager or Admin can manage Clients and Bookings
    Route::middleware('permission:manage_bookings')->group(function() {
        Route::apiResource('clients', App\Http\Controllers\ClientController::class);
        
        Route::post('bookings/check-availability', [App\Http\Controllers\BookingController::class, 'checkAvailability']);
        Route::post('bookings/calculate-price', [App\Http\Controllers\BookingController::class, 'calculatePrice']);
        Route::apiResource('bookings', App\Http\Controllers\BookingController::class);
    });
});
