<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WarrantyClaimController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Web interface routes
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/claims', function () {
    return view('claims.index');
})->name('claims.index');

Route::get('/claims/create', function () {
    return view('claims.create');
})->name('claims.create');

Route::get('/claims/{id}', function ($id) {
    return view('claims.show', compact('id'));
})->name('claims.show');