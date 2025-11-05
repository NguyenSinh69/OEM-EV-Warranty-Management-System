<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController; // Quan trọng

// (Yêu cầu: 3 API)

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