<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Support\AccessControl;
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
        })->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)->name('dashboard');

        Route::get('/profile', fn () => inertia('Member/Pages/Placeholder', [
            'title' => 'Profil Saya',
            'description' => 'Profil ahli akan dibina dalam fasa modul ahli. Laluan ini dilindungi untuk akaun ahli sahaja.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)->name('profile');

        Route::get('/documents', fn () => inertia('Member/Pages/Placeholder', [
            'title' => 'Dokumen Saya',
            'description' => 'Dokumen ahli akan tersedia apabila modul dokumen dibina.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)->name('documents.index');

        Route::get('/applications', fn () => inertia('Member/Pages/Placeholder', [
            'title' => 'Permohonan Saya',
            'description' => 'Status permohonan akan tersedia apabila modul permohonan keahlian dibina.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)->name('applications.index');

        Route::get('/complaints', fn () => inertia('Member/Pages/Placeholder', [
            'title' => 'Aduan Saya',
            'description' => 'Aduan dan cadangan ahli akan dibina dalam fasa sokongan.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)->name('complaints.index');
    });
});
