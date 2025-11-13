<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController; // Quan trọng

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/customers', [CustomerController::class, 'store']);
Route::get('/customers/{id}', [CustomerController::class, 'show']);
Route::get('/customers', [CustomerController::class, 'index']);

// === KẾT THÚC CODE TICKET #10 ===


// Health Check (Giữ lại nếu cần)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'service' => 'customer-service',
    ]);
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    
    // Protected routes
    Route::middleware('jwt.auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

// Customer Routes (Protected)
Route::middleware('jwt.auth')->prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'store']); // Đây là API cho Ticket #21
    Route::get('/{id}', [CustomerController::class, 'show']);
    Route::put('/{id}', [CustomerController::class, 'update']);
    Route::delete('/{id}', [CustomerController::class, 'destroy']);
    
});

// Public Routes for other services
Route::prefix('public')->group(function () {
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::post('/customers/validate', function (Request $request) {
        // Validate customer exists for other services
        return response()->json([
            'valid' => true,
            'customer_id' => $request->customer_id
        ]);
    });
});

// Internal service-to-service communication
Route::prefix('internal')->group(function () {
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::post('/customers/bulk', function (Request $request) {
        // Bulk customer data for reports
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Bulk customer data retrieved'
        ]);
    });
});