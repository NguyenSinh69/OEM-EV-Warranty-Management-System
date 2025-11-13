<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehicleController; // Import Controller

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 4 API cho Ticket #21 (Nhiệm vụ 2)
Route::post('/vehicles/register', [VehicleController::class, 'register']);
Route::post('/vehicles/{vin}/parts', [VehicleController::class, 'addParts']);
Route::get('/vehicles/{vin}/history', [VehicleController::class, 'getHistory']);
Route::post('/vehicles/{vin}/service', [VehicleController::class, 'addService']);


// Route mặc định
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});