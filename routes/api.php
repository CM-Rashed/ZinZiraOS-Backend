<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AuthController as AdminApiController;
use App\Http\Controllers\Staff\AuthController as StaffApiController;
use App\Http\Controllers\User\AuthController as UserApiController;

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\HomePageController;
use App\Http\Controllers\Admin\AboutUsController;

use App\Http\Controllers\Staff\AttendanceController;

use App\Http\Controllers\User\UserOrderController;
use App\Http\Controllers\User\UserProductController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Public Admin Auth Routes
Route::prefix('admin')->group(function () {
    Route::post('/request-otp', [AdminApiController::class, 'requestRegistrationOtp']);
    Route::post('/complete-registration', [AdminApiController::class, 'completeRegistration']);
    Route::post('/login', [AdminApiController::class, 'login']);
    Route::post('/complete-login', [AdminApiController::class, 'completeLogin']);
});

// Public Staff Auth Routes (2-Step Email OTP Registration & Login)
Route::prefix('staff')->group(function () {
    Route::post('/request-otp', [StaffApiController::class, 'requestRegistrationOtp']);
    Route::post('/complete-registration', [StaffApiController::class, 'completeRegistration']);
    Route::post('/login', [StaffApiController::class, 'login']);
    Route::post('/complete-login', [StaffApiController::class, 'completeLogin']);
});

// Public User Auth Routes
Route::prefix('user')->group(function () {
    Route::post('/request-otp', [UserApiController::class, 'requestRegistrationOtp']);
    Route::post('/complete-registration', [UserApiController::class, 'completeRegistration']);
    Route::post('/login', [UserApiController::class, 'login']);
    Route::post('/complete-login', [UserApiController::class, 'completeLogin']);
    Route::get('shop-settings', [HomePageController::class, 'index'])->name('shop.settings.index');
    Route::get('about-settings', [AboutUsController::class, 'index'])->name('about.settings.index');
});

// Public Catalog Routes
Route::get('/products', [UserProductController::class, 'index']);
Route::get('/products/{product}', [UserProductController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

// Protected Admin Endpoints
Route::middleware('auth:admin-api')->prefix('admin')->group(function () {
    Route::post('/logout', [AdminApiController::class, 'logout']);
    Route::get('/me', fn (Request $request) => $request->user());

    // Management Resources
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('staff', StaffApiController::class); // Endpoint: /api/admin/staff

    // Attendance Management
    Route::get('attendance', [AttendanceController::class, 'index']);
    Route::post('attendance/daily', [AttendanceController::class, 'storeDaily']);
    Route::put('attendance/{attendance}', [AttendanceController::class, 'update']);

    // Orders & Inventory
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::apiResource('orders', OrderController::class);
    Route::get('inventories/{productId}', [InventoryController::class, 'show']);
    Route::post('inventories', [InventoryController::class, 'store']);

    // Analytics & Settings
    Route::prefix('reports')->group(function () {
        Route::get('revenue', [ReportController::class, 'revenue']);
        Route::get('cost', [ReportController::class, 'cost']);
        Route::get('summary', [ReportController::class, 'summary']);
    });

    Route::get('shop-settings', [HomePageController::class, 'index'])->name('shop.settings.index');
    Route::post('shop-settings', [HomePageController::class, 'update'])->name('shop.settings.update');
    Route::get('about-settings', [AboutUsController::class, 'index'])->name('about.settings.index');
    Route::post('about-settings', [AboutUsController::class, 'update'])->name('about.settings.update');
});

// Protected Staff Endpoints
Route::middleware('auth:staff-api')->prefix('staff')->group(function () {
    Route::post('/logout', [StaffApiController::class, 'logout']);
    Route::get('/me', fn (Request $request) => $request->user());

    Route::apiResource('customers', CustomerController::class);
    Route::get('sales/{staff_name}', [OrderController::class, 'byStaff']);

    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::apiResource('orders', OrderController::class);
});

// Protected User Endpoints
Route::middleware('auth:user-api')->prefix('user')->group(function () {
    Route::post('/logout', [UserApiController::class, 'logout']);
    Route::get('/me', fn (Request $request) => $request->user());
    
    Route::apiResource('orders', UserOrderController::class);
});