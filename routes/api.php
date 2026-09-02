<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminApiController;
use App\Http\Controllers\Staff\AuthController as StaffApiController;
use App\Http\Controllers\User\AuthController as UserApiController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Web\ProductController as WebProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\User\UserOrderController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\User\UserProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Staff\AttendanceController;
/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/

// Admin Public Routes
Route::post('/admin/register', [AdminApiController::class, 'register']);
Route::post('/admin/login', [AdminApiController::class, 'login']);

// Staff Public Routes
Route::post('/staff/login', [StaffApiController::class, 'login']);

// User Public Routes
Route::post('/user/register', [UserApiController::class, 'register']);
Route::post('/user/login', [UserApiController::class, 'login']);

// Public Products
Route::get('/products', [UserProductController::class, 'index']);
Route::get('/products/{product}', [UserProductController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Protected Auth Routes
|--------------------------------------------------------------------------
*/

// Protected Admin Endpoints
Route::middleware('auth:admin-api')->prefix('admin')->group(function () {
    Route::post('/logout', [AdminApiController::class, 'logout']);
    Route::get('/me', fn (Request $request) => $request->user());
    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/products', ProductController::class);
    Route::apiResource('/customers', CustomerController::class);
    Route::apiResource('staff', StaffApiController::class);
    Route::get('/attendance', [AttendanceController::class, 'index']); // Accepts start_date, end_date, filter, staff_id
    Route::post('/attendance/daily', [AttendanceController::class, 'storeDaily']); // Submit daily list from UI
    Route::put('/attendance/{attendance}', [AttendanceController::class, 'update']);
    
    // Status update endpoint for orders
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::apiResource('orders', OrderController::class);

    Route::get('/inventories/{productId}', [InventoryController::class, 'show']);
    Route::post('/inventories', [InventoryController::class, 'store']);

    // Reports Endpoints
    Route::prefix('reports')->group(function () {
        Route::get('/revenue', [ReportController::class, 'revenue']);
        Route::get('/cost', [ReportController::class, 'cost']);
        Route::get('/summary', [ReportController::class, 'summary']);
    });
});

// Protected Staff Endpoints
Route::middleware('auth:staff-api')->prefix('staff')->group(function () {
    Route::post('/logout', [StaffApiController::class, 'logout']);
    Route::get('/me', fn (Request $request) => $request->user());
    Route::apiResource('/customers', CustomerController::class);
    Route::apiResource('staff', StaffApiController::class);

    // Dedicated sales/staff route
    Route::get('sales/{staff_name}', [OrderController::class, 'byStaff']);

    // Status update endpoint for orders
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::apiResource('orders', OrderController::class);
});

// Protected User Endpoints
Route::middleware('auth:user-api')->prefix('user')->group(function () {
    Route::post('/logout', [UserApiController::class, 'logout']);
    Route::get('/me', fn (Request $request) => $request->user());
    Route::apiResource('/orders', UserOrderController::class);
});