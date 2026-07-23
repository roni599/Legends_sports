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

    // Dashboard (Accessible to any authenticated user, but data scoped if needed later)
    Route::get('dashboard/stats', [App\Http\Controllers\DashboardController::class, 'stats']);
    Route::get('dashboard/chart', [App\Http\Controllers\DashboardController::class, 'chart']);

    // Expenses
    // We will use view_bookings permission as a placeholder for financial access until a specific finance permission is added
    Route::middleware('permission:view_bookings')->apiResource('expense-categories', App\Http\Controllers\ExpenseCategoryController::class);
    Route::middleware(['permission:view_bookings', App\Http\Middleware\CheckMonthLock::class])->apiResource('expenses', App\Http\Controllers\ExpenseController::class);

    // Suppliers & Purchases
    Route::middleware('permission:view_bookings')->apiResource('suppliers', App\Http\Controllers\SupplierController::class);
    Route::middleware('permission:view_bookings')->get('suppliers/{supplier}/ledger', [App\Http\Controllers\SupplierController::class, 'ledger']);
    Route::middleware(['permission:view_bookings', App\Http\Middleware\CheckMonthLock::class])->post('suppliers/{supplier}/pay', [App\Http\Controllers\SupplierController::class, 'paySupplier']);
    Route::middleware(['permission:view_bookings', App\Http\Middleware\CheckMonthLock::class])->post('suppliers/{supplier}/refund', [App\Http\Controllers\SupplierController::class, 'receiveRefund']);
    
    Route::middleware(['permission:create_bookings', App\Http\Middleware\CheckMonthLock::class])->apiResource('purchases', App\Http\Controllers\PurchaseController::class);

    // POS & Products
    Route::middleware('permission:view_bookings')->apiResource('products', App\Http\Controllers\ProductController::class);
    Route::middleware(['permission:create_bookings', App\Http\Middleware\CheckMonthLock::class])->post('pos/checkout', [App\Http\Controllers\POSController::class, 'checkout']);

    // Accounting & Month Closing
    Route::middleware('permission:view_users')->get('monthly-closings', [App\Http\Controllers\MonthlyClosingController::class, 'index']);
    Route::middleware('permission:view_users')->post('monthly-closings', [App\Http\Controllers\MonthlyClosingController::class, 'store']);
    Route::get('monthly-closings/check', [App\Http\Controllers\MonthlyClosingController::class, 'check']);

    // Roles
    Route::middleware('permission:view_users')->get('roles', [App\Http\Controllers\RoleController::class, 'index']);
    Route::middleware('permission:create_users')->post('roles', [App\Http\Controllers\RoleController::class, 'store']);
    Route::middleware('permission:view_users')->get('roles/{role}', [App\Http\Controllers\RoleController::class, 'show']);
    Route::middleware('permission:edit_users')->put('roles/{role}', [App\Http\Controllers\RoleController::class, 'update']);
    Route::middleware('permission:delete_users')->delete('roles/{role}', [App\Http\Controllers\RoleController::class, 'destroy']);

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
    Route::middleware('permission:edit_grounds')->patch('pricing-rules/{pricing_rule}/toggle-status', [App\Http\Controllers\PricingRuleController::class, 'toggleStatus']);
    Route::middleware('permission:delete_grounds')->delete('pricing-rules/{pricing_rule}', [App\Http\Controllers\PricingRuleController::class, 'destroy']);

    // Clients
    Route::middleware('permission:view_clients')->get('clients', [App\Http\Controllers\ClientController::class, 'index']);
    Route::middleware('permission:create_clients')->post('clients', [App\Http\Controllers\ClientController::class, 'store']);
    Route::middleware('permission:view_clients')->get('clients/{client}', [App\Http\Controllers\ClientController::class, 'show']);
    Route::middleware('permission:view_clients')->get('clients/{client}/ledger', [App\Http\Controllers\ClientController::class, 'ledger']);
    Route::middleware('permission:edit_clients')->post('clients/{client}/receive-payment', [App\Http\Controllers\ClientController::class, 'receiveDuePayment']);
    Route::middleware('permission:view_clients')->get('clients/{client}/due-invoices', [App\Http\Controllers\ClientController::class, 'getDueInvoices']);
    Route::middleware('permission:view_clients')->get('clients/{client}/advance-invoices', [App\Http\Controllers\ClientController::class, 'getAdvanceInvoices']);
    Route::middleware('permission:edit_clients')->post('clients/{client}/receive-invoices', [App\Http\Controllers\ClientController::class, 'receiveDueInvoices']);
    Route::middleware('permission:view_clients')->get('clients/{client}/refundable-invoices', [App\Http\Controllers\ClientController::class, 'getRefundableInvoices']);
    Route::middleware('permission:edit_clients')->post('clients/{client}/refund-invoices', [App\Http\Controllers\ClientController::class, 'refundInvoices']);
    Route::middleware('permission:edit_clients')->post('clients/{client}/pay-out', [App\Http\Controllers\ClientController::class, 'payOut']);
    Route::middleware('permission:edit_clients')->post('clients/{client}/advance', [App\Http\Controllers\ClientController::class, 'processAdvance']);
    Route::middleware('permission:edit_clients')->post('clients/{client}/dismiss-invoices', [App\Http\Controllers\ClientController::class, 'dismissDueInvoices']);
    Route::middleware('permission:view_clients')->get('client-transactions', [App\Http\Controllers\ClientTransactionController::class, 'index']);
    Route::middleware('permission:view_clients')->get('client-transactions/{payment}', [App\Http\Controllers\ClientTransactionController::class, 'show']);
    Route::middleware('permission:edit_clients')->put('client-transactions/{payment}', [App\Http\Controllers\ClientTransactionController::class, 'update']);
    Route::middleware('permission:view_clients')->get('payments/{payment}', [App\Http\Controllers\PaymentController::class, 'show']);
    Route::middleware('permission:edit_clients')->patch('clients/{client}/toggle-status', [App\Http\Controllers\ClientController::class, 'toggleStatus']);
    Route::middleware('permission:edit_clients')->put('clients/{client}', [App\Http\Controllers\ClientController::class, 'update']);
    Route::middleware('permission:delete_clients')->delete('clients/{client}', [App\Http\Controllers\ClientController::class, 'destroy']);

    // Bookings
    Route::middleware('permission:view_bookings')->post('bookings/check-availability', [App\Http\Controllers\BookingController::class, 'checkAvailability']);
    Route::middleware('permission:view_bookings')->post('bookings/calculate-price', [App\Http\Controllers\BookingController::class, 'calculatePrice']);
    
    Route::middleware('permission:view_bookings')->get('bookings', [App\Http\Controllers\BookingController::class, 'index']);
    Route::middleware(['permission:create_bookings', App\Http\Middleware\CheckMonthLock::class])->post('bookings', [App\Http\Controllers\BookingController::class, 'store']);
    Route::middleware('permission:view_bookings')->get('bookings/{booking}', [App\Http\Controllers\BookingController::class, 'show']);
    Route::middleware(['permission:edit_bookings', App\Http\Middleware\CheckMonthLock::class])->put('bookings/{booking}', [App\Http\Controllers\BookingController::class, 'update']);
    Route::middleware(['permission:delete_bookings', App\Http\Middleware\CheckMonthLock::class])->delete('bookings/{booking}', [App\Http\Controllers\BookingController::class, 'destroy']);

    // Reports (Phase 4)
    Route::middleware('permission:view_bookings')->get('reports/income-expense', [App\Http\Controllers\ReportController::class, 'incomeExpense']);
    Route::middleware('permission:view_bookings')->get('reports/cash-flow', [App\Http\Controllers\ReportController::class, 'cashFlow']);
    Route::middleware('permission:view_bookings')->get('reports/{type}', [App\Http\Controllers\ReportController::class, 'genericReport']);
});
