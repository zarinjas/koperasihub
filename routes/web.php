<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Public\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('public.home');

Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '^(?!admin$|member$|api$|login$|register$|dashboard$|storage$|assets$)[A-Za-z0-9-]+$')
    ->name('public.pages.show');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
