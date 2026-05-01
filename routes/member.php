<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('member')->name('member.')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'createMember'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');
    Route::post('/quick-login', [AuthenticatedSessionController::class, 'quickLoginMember'])
        ->name('quick-login');

    Route::middleware('area:member')->group(function (): void {
        Route::redirect('/', '/member/dashboard')->name('home');

        Route::get('/dashboard', function () {
            return inertia('Member/Pages/Dashboard');
        })->name('dashboard');
    });
});
