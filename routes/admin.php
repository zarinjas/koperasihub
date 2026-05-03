<?php

use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Support\AccessControl;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'createAdmin'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');
    Route::post('/quick-login', [AuthenticatedSessionController::class, 'quickLoginAdmin'])
        ->name('quick-login');
    Route::post('/quick-login/super-admin', [AuthenticatedSessionController::class, 'quickLoginSuperAdmin'])
        ->name('quick-login.super-admin');

    Route::middleware('area:admin')->group(function (): void {
        Route::redirect('/', '/admin/dashboard')->name('home');

        Route::get('/dashboard', function () {
            return inertia('Admin/Pages/Dashboard');
        })->middleware('permission:'.AccessControl::PERMISSION_VIEW_ADMIN_DASHBOARD)->name('dashboard');

        Route::redirect('/pages', '/admin/cms/pages')
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_PAGES);

        Route::get('/cms/pages', [PageController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_PAGES)
            ->name('pages.index');
        Route::get('/cms/pages/create', [PageController::class, 'create'])
            ->middleware('permission:'.AccessControl::PERMISSION_CREATE_PAGES)
            ->name('pages.create');
        Route::post('/cms/pages', [PageController::class, 'store'])
            ->middleware('permission:'.AccessControl::PERMISSION_CREATE_PAGES)
            ->name('pages.store');
        Route::get('/cms/pages/{page}/edit', [PageController::class, 'edit'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_PAGES)
            ->name('pages.edit');
        Route::match(['put', 'patch'], '/cms/pages/{page}', [PageController::class, 'update'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_PAGES)
            ->name('pages.update');
        Route::post('/cms/pages/{page}/publish', [PageController::class, 'publish'])
            ->middleware('permission:'.AccessControl::PERMISSION_PUBLISH_PAGES)
            ->name('pages.publish');
        Route::post('/cms/pages/{page}/unpublish', [PageController::class, 'unpublish'])
            ->middleware('permission:'.AccessControl::PERMISSION_PUBLISH_PAGES)
            ->name('pages.unpublish');
        Route::post('/cms/pages/{page}/archive', [PageController::class, 'archive'])
            ->middleware('permission:'.AccessControl::PERMISSION_PUBLISH_PAGES)
            ->name('pages.archive');
        Route::get('/cms/pages/{page}/sections', [PageSectionController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_PAGES)
            ->name('pages.sections.index');
        Route::post('/cms/pages/{page}/sections', [PageSectionController::class, 'store'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_PAGES)
            ->name('pages.sections.store');
        Route::post('/cms/pages/{page}/sections/reorder', [PageSectionController::class, 'reorder'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_PAGES)
            ->name('pages.sections.reorder');
        Route::match(['put', 'patch'], '/page-sections/{pageSection}', [PageSectionController::class, 'update'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_PAGES)
            ->name('page-sections.update');
        Route::delete('/page-sections/{pageSection}', [PageSectionController::class, 'destroy'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_PAGES)
            ->name('page-sections.destroy');

        Route::get('/media', [MediaController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_MEDIA)
            ->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])
            ->middleware('permission:'.AccessControl::PERMISSION_UPLOAD_MEDIA)
            ->name('media.store');
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])
            ->middleware('permission:'.AccessControl::PERMISSION_DELETE_MEDIA)
            ->name('media.destroy');

        Route::get('/services', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Perkhidmatan',
            'description' => 'Pengurusan perkhidmatan akan dibina selepas asas CMS tersedia.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_SERVICES)->name('services.index');

        Route::get('/announcements', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Pengumuman',
            'description' => 'Modul pengumuman akan ditambah dalam fasa modul kandungan.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_ANNOUNCEMENTS)->name('announcements.index');

        Route::get('/documents', [DocumentController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_DOCUMENTS)
            ->name('documents.index');
        Route::get('/documents/create', [DocumentController::class, 'create'])
            ->middleware('permission:'.AccessControl::PERMISSION_CREATE_DOCUMENTS)
            ->name('documents.create');
        Route::post('/documents', [DocumentController::class, 'store'])
            ->middleware('permission:'.AccessControl::PERMISSION_CREATE_DOCUMENTS)
            ->name('documents.store');
        Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_DOCUMENTS)
            ->name('documents.edit');
        Route::match(['put', 'patch'], '/documents/{document}', [DocumentController::class, 'update'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_DOCUMENTS)
            ->name('documents.update');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
            ->middleware('permission:'.AccessControl::PERMISSION_DELETE_DOCUMENTS)
            ->name('documents.destroy');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_DOCUMENTS)
            ->name('documents.download');

        Route::get('/members', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Ahli',
            'description' => 'Modul ahli akan dibina selepas asas tetapan dan CMS.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_MEMBERS)->name('members.index');

        Route::get('/membership-applications', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Permohonan Keahlian',
            'description' => 'Aliran semakan permohonan akan dibina dalam fasa keahlian.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_MEMBERSHIP_APPLICATIONS)->name('membership-applications.index');

        Route::get('/complaints', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Aduan dan Cadangan',
            'description' => 'Modul sokongan ahli akan ditambah pada fasa yang ditetapkan.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_COMPLAINTS)->name('complaints.index');

        Route::get('/users', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Pengguna',
            'description' => 'UI pengurusan pengguna belum dibina dalam Fasa 2, tetapi akses laluan telah dikawal.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_USERS)->name('users.index');

        Route::get('/roles', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Peranan',
            'description' => 'UI pengurusan peranan tidak termasuk dalam skop Fasa 2.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_ROLES)->name('roles.index');

        Route::get('/settings', [SettingsController::class, 'edit'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_SETTINGS)
            ->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_SETTINGS)
            ->name('settings.update');

        Route::get('/audit-logs', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Log Audit',
            'description' => 'Paparan log audit akan dibina selepas tindakan sensitif mula direkodkan.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_AUDIT_LOGS)->name('audit-logs.index');

        Route::get('/reports', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Laporan',
            'description' => 'Laporan operasi asas akan dibina selepas modul data utama tersedia.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_REPORTS)->name('reports.index');
    });
});
