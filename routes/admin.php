<?php

use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageSectionController;
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

    Route::middleware('area:admin')->group(function (): void {
        Route::redirect('/', '/admin/dashboard')->name('home');

        Route::get('/dashboard', function () {
            return inertia('Admin/Pages/Dashboard');
        })->middleware('permission:'.AccessControl::PERMISSION_VIEW_ADMIN_DASHBOARD)->name('dashboard');

        Route::get('/pages', [PageController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_PAGES)
            ->name('pages.index');
        Route::post('/pages', [PageController::class, 'store'])
            ->middleware('permission:'.AccessControl::PERMISSION_CREATE_PAGES)
            ->name('pages.store');
        Route::get('/pages/{page}', [PageController::class, 'show'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_PAGES)
            ->name('pages.show');
        Route::match(['put', 'patch'], '/pages/{page}', [PageController::class, 'update'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_PAGES)
            ->name('pages.update');
        Route::post('/pages/{page}/publish', [PageController::class, 'publish'])
            ->middleware('permission:'.AccessControl::PERMISSION_PUBLISH_PAGES)
            ->name('pages.publish');
        Route::post('/pages/{page}/archive', [PageController::class, 'archive'])
            ->middleware('permission:'.AccessControl::PERMISSION_PUBLISH_PAGES)
            ->name('pages.archive');
        Route::get('/pages/{page}/sections', [PageSectionController::class, 'index'])
            ->middleware('permission:'.AccessControl::PERMISSION_VIEW_PAGES)
            ->name('pages.sections.index');
        Route::post('/pages/{page}/sections', [PageSectionController::class, 'store'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_PAGES)
            ->name('pages.sections.store');
        Route::post('/pages/{page}/sections/reorder', [PageSectionController::class, 'reorder'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_PAGES)
            ->name('pages.sections.reorder');
        Route::match(['put', 'patch'], '/page-sections/{pageSection}', [PageSectionController::class, 'update'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_PAGES)
            ->name('page-sections.update');
        Route::delete('/page-sections/{pageSection}', [PageSectionController::class, 'destroy'])
            ->middleware('permission:'.AccessControl::PERMISSION_EDIT_PAGES)
            ->name('page-sections.destroy');

        Route::get('/media', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Media',
            'description' => 'Pustaka media akan ditambah bersama modul CMS. Laluan ini telah dilindungi mengikut kebenaran.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_MEDIA)->name('media.index');

        Route::get('/services', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Perkhidmatan',
            'description' => 'Pengurusan perkhidmatan akan dibina selepas asas CMS tersedia.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_SERVICES)->name('services.index');

        Route::get('/announcements', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Pengumuman',
            'description' => 'Modul pengumuman akan ditambah dalam fasa modul kandungan.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_ANNOUNCEMENTS)->name('announcements.index');

        Route::get('/documents', fn () => inertia('Admin/Pages/Placeholder', [
            'title' => 'Dokumen',
            'description' => 'Pengurusan dokumen akan dibina sebagai modul berasingan kemudian.',
        ]))->middleware('permission:'.AccessControl::PERMISSION_VIEW_DOCUMENTS)->name('documents.index');

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
