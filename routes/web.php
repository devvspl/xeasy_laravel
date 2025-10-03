<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\admin\DashboardController;
use Illuminate\Support\Facades\Route;

// Public routes (accessible without login)

// Use / for both GET (show form) and POST (login)
Route::match(['get', 'post'], '/', [AuthController::class, 'login'])->name('login');

// Optional: keep /login as alias for GET form
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login.form');

// Extra routes
Route::get('xeasy_login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes (accessible only to logged-in users)
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
