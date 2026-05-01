<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'createAdmin'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');
    Route::post('/quick-login', [AuthenticatedSessionController::class, 'quickLoginAdmin'])
        ->name('quick-login');

    Route::middleware('area:admin')->group(function (): void {
        Route::redirect('/', '/admin/dashboard')->name('home');

        Route::get('/dashboard', function () {
            return inertia('Admin/Pages/Dashboard');
        })->name('dashboard');
    });
});
