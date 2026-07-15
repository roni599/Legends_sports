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

    // Users
    Route::middleware('permission:view_users')->get('users', [App\Http\Controllers\UserController::class, 'index']);
    Route::middleware('permission:view_users')->get('users/roles', [App\Http\Controllers\UserController::class, 'roles']);
    Route::middleware('permission:view_users')->get('users/permissions', [App\Http\Controllers\UserController::class, 'permissions']);
    Route::middleware('permission:create_users')->post('users', [App\Http\Controllers\UserController::class, 'store']);
    Route::middleware('permission:view_users')->get('users/{user}', [App\Http\Controllers\UserController::class, 'show']);
    Route::middleware('permission:edit_users')->put('users/{user}', [App\Http\Controllers\UserController::class, 'update']);
    Route::middleware('permission:delete_users')->delete('users/{user}', [App\Http\Controllers\UserController::class, 'destroy']);

    // Grounds & Pricing
    Route::middleware('permission:view_grounds')->get('grounds', [App\Http\Controllers\GroundController::class, 'index']);
    Route::middleware('permission:create_grounds')->post('grounds', [App\Http\Controllers\GroundController::class, 'store']);
    Route::middleware('permission:view_grounds')->get('grounds/{ground}', [App\Http\Controllers\GroundController::class, 'show']);
    Route::middleware('permission:edit_grounds')->put('grounds/{ground}', [App\Http\Controllers\GroundController::class, 'update']);
    Route::middleware('permission:delete_grounds')->delete('grounds/{ground}', [App\Http\Controllers\GroundController::class, 'destroy']);
    
    Route::middleware('permission:view_grounds')->get('pricing-rules', [App\Http\Controllers\PricingRuleController::class, 'index']);
    Route::middleware('permission:create_grounds')->post('pricing-rules', [App\Http\Controllers\PricingRuleController::class, 'store']);
    Route::middleware('permission:view_grounds')->get('pricing-rules/{pricing_rule}', [App\Http\Controllers\PricingRuleController::class, 'show']);
    Route::middleware('permission:edit_grounds')->put('pricing-rules/{pricing_rule}', [App\Http\Controllers\PricingRuleController::class, 'update']);
    Route::middleware('permission:delete_grounds')->delete('pricing-rules/{pricing_rule}', [App\Http\Controllers\PricingRuleController::class, 'destroy']);

    // Clients
    Route::middleware('permission:view_clients')->get('clients', [App\Http\Controllers\ClientController::class, 'index']);
    Route::middleware('permission:create_clients')->post('clients', [App\Http\Controllers\ClientController::class, 'store']);
    Route::middleware('permission:view_clients')->get('clients/{client}', [App\Http\Controllers\ClientController::class, 'show']);
    Route::middleware('permission:edit_clients')->put('clients/{client}', [App\Http\Controllers\ClientController::class, 'update']);
    Route::middleware('permission:delete_clients')->delete('clients/{client}', [App\Http\Controllers\ClientController::class, 'destroy']);

    // Bookings
    Route::middleware('permission:view_bookings')->post('bookings/check-availability', [App\Http\Controllers\BookingController::class, 'checkAvailability']);
    Route::middleware('permission:view_bookings')->post('bookings/calculate-price', [App\Http\Controllers\BookingController::class, 'calculatePrice']);
    
    Route::middleware('permission:view_bookings')->get('bookings', [App\Http\Controllers\BookingController::class, 'index']);
    Route::middleware('permission:create_bookings')->post('bookings', [App\Http\Controllers\BookingController::class, 'store']);
    Route::middleware('permission:view_bookings')->get('bookings/{booking}', [App\Http\Controllers\BookingController::class, 'show']);
    Route::middleware('permission:edit_bookings')->put('bookings/{booking}', [App\Http\Controllers\BookingController::class, 'update']);
    Route::middleware('permission:delete_bookings')->delete('bookings/{booking}', [App\Http\Controllers\BookingController::class, 'destroy']);
});
