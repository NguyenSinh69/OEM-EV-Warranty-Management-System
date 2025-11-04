<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WarrantyClaimController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Warranty Claims API
Route::prefix('warranty-claims')->group(function () {
    Route::get('/', [WarrantyClaimController::class, 'index']);
    Route::post('/', [WarrantyClaimController::class, 'store']);
    Route::get('/statistics', [WarrantyClaimController::class, 'statistics']);
    Route::get('/{warrantyClaim}', [WarrantyClaimController::class, 'show']);
    Route::patch('/{warrantyClaim}/status', [WarrantyClaimController::class, 'updateStatus']);
    Route::get('/{warrantyClaim}/transitions', [WarrantyClaimController::class, 'getAvailableTransitions']);
});

// Customers API
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'store']);
    Route::get('/{customer}', [CustomerController::class, 'show']);
    Route::put('/{customer}', [CustomerController::class, 'update']);
});

// Products API
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{product}', [ProductController::class, 'show']);
    Route::put('/{product}', [ProductController::class, 'update']);
});