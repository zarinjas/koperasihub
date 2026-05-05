<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Member\AnnouncementController;
use App\Http\Controllers\Member\ApplicationController;
use App\Http\Controllers\Member\ComplaintController;
use App\Http\Controllers\Member\CardController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\DocumentController;
use App\Http\Controllers\Member\MembershipApplicationController;
use App\Http\Controllers\Member\ProfileController;
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

        Route::get('/dashboard', DashboardController::class)
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('dashboard');

        Route::get('/card', [CardController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('card');
        Route::get('/card/{member}', [CardController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('card.show');

        Route::get('/profile', [ProfileController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('profile');
        Route::patch('/profile', [ProfileController::class, 'update'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('profile.update');

        Route::get('/documents', [DocumentController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('documents.index');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('documents.download');

        Route::get('/applications', [ApplicationController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('applications.index');
        Route::get('/applications/submissions/{submission}', [ApplicationController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('applications.submissions.show');

        Route::get('/announcements', [AnnouncementController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('announcements.index');

        Route::get('/complaints', [ComplaintController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('complaints.index');
        Route::get('/complaints/create', [ComplaintController::class, 'create'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('complaints.create');
        Route::post('/complaints', [ComplaintController::class, 'store'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('complaints.store');
        Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('complaints.show');
    });
});
