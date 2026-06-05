<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Member\ActivationController;
use App\Http\Controllers\Member\AnnouncementController;
use App\Http\Controllers\Member\ApplicationController;
use App\Http\Controllers\Member\CardController;
use App\Http\Controllers\Member\CarumanController as MemberCarumanController;
use App\Http\Controllers\Member\ComplaintController;
use App\Http\Controllers\Member\KoperasiAIChatController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\DocumentController;
use App\Http\Controllers\Member\FinancingApplicationController;
use App\Http\Controllers\Member\FinancingController;
use App\Http\Controllers\Member\Financing\FinancingGeneratedDocumentController;
use App\Http\Controllers\Member\FinancingGuarantorController;
use App\Http\Controllers\Member\NotificationController;
use App\Http\Controllers\Member\PasswordResetController;
use App\Http\Controllers\Member\PopupDismissController;
use App\Http\Controllers\Member\PosterController;
use App\Http\Controllers\Member\ProgramController as MemberProgramController;
use App\Http\Controllers\Member\ProfileController;
use App\Http\Controllers\Member\FormController as MemberFormController;
use App\Http\Controllers\Member\AnsuranApplicationController as MemberAnsuranApplicationController;
use App\Http\Controllers\Member\AnsuranCatalogController as MemberAnsuranCatalogController;
use App\Http\Controllers\Member\AnsuranGuarantorController as MemberAnsuranGuarantorController;
use App\Http\Controllers\Member\ReferralController;
use App\Support\AccessControl;
use Illuminate\Support\Facades\Route;

Route::prefix('member')->name('member.')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'createMember'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');
    Route::post('/quick-login', [AuthenticatedSessionController::class, 'quickLoginMember'])
        ->name('quick-login');

    Route::get('/activate', [ActivationController::class, 'create'])
        ->name('activate');
    Route::post('/activate', [ActivationController::class, 'verifyStep1'])
        ->name('activate.verify');
    Route::post('/activate/complete', [ActivationController::class, 'complete'])
        ->name('activate.complete');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.update');

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
        Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('profile.photo.upload');

        Route::get('/documents', [DocumentController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('documents.index');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('documents.download');

        Route::get('/financing', [FinancingController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.index');
        Route::get('/financing/guarantor-search', [FinancingController::class, 'guarantorSearch'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.guarantor-search');
        Route::get('/financing/products/{product}', [FinancingController::class, 'showProduct'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.products.show');

        Route::get('/financing/applications', [FinancingApplicationController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.index');
        Route::post('/financing/applications', [FinancingApplicationController::class, 'store'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.store');
        Route::get('/financing/applications/create', [FinancingApplicationController::class, 'create'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.create');
        Route::get('/financing/applications/{application}', [FinancingApplicationController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.show');

        Route::get('/financing/applications/{application}/generated-documents/{document}/download', [FinancingGeneratedDocumentController::class, 'download'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.generated-documents.download');
        Route::post('/financing/applications/{application}/generated-documents/{document}/upload', [FinancingGeneratedDocumentController::class, 'upload'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.generated-documents.upload');

        Route::get('/financing/applications/{application}/print', [FinancingApplicationController::class, 'print'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.print');
        Route::post('/financing/applications/{application}/cancel', [FinancingApplicationController::class, 'cancel'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.cancel');
        Route::post('/financing/applications/{application}/documents', [FinancingApplicationController::class, 'uploadDocument'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.documents.store');
        Route::get('/financing/applications/{application}/documents/{document}/download', [FinancingApplicationController::class, 'downloadDocument'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.documents.download');
        Route::post('/financing/applications/{application}/stamped-form', [FinancingApplicationController::class, 'uploadStampedForm'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.stamped-form.store');
        Route::post('/financing/applications/{application}/supporting-documents', [FinancingApplicationController::class, 'uploadSupportingDocument'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.applications.supporting-documents.upload');

        Route::get('/financing/guarantor-requests', [FinancingGuarantorController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.guarantor-requests.index');
        Route::get('/financing/calculator', [FinancingController::class, 'calculator'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.calculator');

        Route::get('/financing/guarantor-requests/{guarantor}', [FinancingGuarantorController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.guarantor-requests.show');
        Route::post('/financing/guarantor-requests/{guarantor}', [FinancingGuarantorController::class, 'respond'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('financing.guarantor-requests.respond');

        Route::get('/applications', [ApplicationController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('applications.index');
        Route::get('/applications/submissions/{submission}', [ApplicationController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('applications.submissions.show');

        Route::get('/forms', [MemberFormController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('forms.index');

        Route::get('/announcements', [AnnouncementController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('announcements.index');
        Route::get('/announcements/{slug}', [AnnouncementController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('announcements.show');

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

        Route::get('/posters', [PosterController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('posters.index');

        Route::get('/caruman', [MemberCarumanController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('caruman.index');

        Route::get('/programs', [MemberProgramController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('programs.index');
        Route::get('/programs/{program}', [MemberProgramController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('programs.show');
        Route::post('/programs/{program}/rsvp', [MemberProgramController::class, 'rsvp'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('programs.rsvp');
        Route::get('/programs/{program}/check-in', [MemberProgramController::class, 'checkIn'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('programs.check-in');
        Route::post('/programs/{program}/check-in', [MemberProgramController::class, 'checkIn'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('programs.check-in.store');

        Route::get('/attendance', [MemberProgramController::class, 'attendanceHistory'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('attendance.index');

        Route::get('/referrals', [ReferralController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('referrals.index');
        Route::post('/referrals/generate', [ReferralController::class, 'generate'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('referrals.generate');

        Route::post('/koperasi-ai-chat', [KoperasiAIChatController::class, 'chat'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('koperasi-ai-chat');

        Route::post('/popup/dismiss', PopupDismissController::class)
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('popup.dismiss');

        Route::get('/ansuran', [MemberAnsuranCatalogController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.index');
        Route::get('/ansuran/products/{product:slug}', [MemberAnsuranCatalogController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.products.show');
        Route::post('/ansuran/apply', [MemberAnsuranCatalogController::class, 'apply'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.apply');
        Route::get('/ansuran/applications', [MemberAnsuranApplicationController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.applications.index');
        Route::get('/ansuran/applications/{application}', [MemberAnsuranApplicationController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.applications.show');
        Route::get('/ansuran/applications/{application}/sign', [MemberAnsuranApplicationController::class, 'sign'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.applications.sign');
        Route::post('/ansuran/applications/{application}/sign', [MemberAnsuranApplicationController::class, 'storeSignature'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.applications.sign.store');
        Route::post('/ansuran/applications/{application}/cancel', [MemberAnsuranApplicationController::class, 'cancel'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.applications.cancel');
        Route::get('/ansuran/guarantor-requests', [MemberAnsuranGuarantorController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.guarantor-requests.index');
        Route::post('/ansuran/guarantor-requests/{guarantor}', [MemberAnsuranGuarantorController::class, 'respond'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.guarantor-requests.respond');
        Route::get('/ansuran/member-search', [MemberAnsuranGuarantorController::class, 'memberSearch'])
            ->middleware('permission:'.AccessControl::PERMISSION_MEMBER_ACCESS)
            ->name('ansuran.member-search');

        Route::get('/notifications', [NotificationController::class, 'index'])
            ->middleware('auth')
            ->name('notifications.index');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->middleware('auth')
            ->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->middleware('auth')
            ->name('notifications.read-all');
    });
});