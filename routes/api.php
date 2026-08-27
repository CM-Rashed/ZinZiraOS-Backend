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
use App\Http\Controllers\User\OrderController;
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
Route::get('/products', [WebProductController::class, 'index']);
Route::apiResource('orders', OrderController::class);
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
});

// Protected Staff Endpoints
Route::middleware('auth:staff-api')->prefix('staff')->group(function () {
    Route::post('/logout', [StaffApiController::class, 'logout']);
    Route::get('/me', fn (Request $request) => $request->user());
    Route::apiResource('/customers', CustomerController::class);
    Route::post('/staff/logout', [StaffApiController::class, 'logout']);
});

// Protected User Endpoints
Route::middleware('auth:user-api')->prefix('user')->group(function () {
    Route::post('/logout', [UserApiController::class, 'logout']);
    Route::get('/me', fn (Request $request) => $request->user());
});