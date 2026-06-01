<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Public\AnsuranCatalogController;
use App\Http\Controllers\Public\MembershipApplicationController;
use App\Http\Controllers\Public\AnnouncementController;
use App\Http\Controllers\Public\DownloadController;
use App\Http\Controllers\Public\FormDirectoryController;
use App\Http\Controllers\Public\MemberVerificationController;
use App\Http\Controllers\Public\NewsController;
use App\Http\Controllers\Public\PosterController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('public.home');
Route::get('/downloads', [DownloadController::class, 'index'])->name('public.downloads.index');
Route::get('/muat-turun', [DownloadController::class, 'index']);
Route::get('/downloads/{document}/download', [DownloadController::class, 'download'])->name('public.downloads.download');
Route::get('/services', [ServiceController::class, 'index'])->name('public.services.index');
Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('public.services.show');
Route::get('/perkhidmatan', [ServiceController::class, 'index']);
Route::get('/perkhidmatan/{slug}', [ServiceController::class, 'show']);
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('public.announcements.index');
Route::get('/announcements/{slug}', [AnnouncementController::class, 'show'])->name('public.announcements.show');
Route::get('/pengumuman', [AnnouncementController::class, 'index']);
Route::get('/pengumuman/{slug}', [AnnouncementController::class, 'show']);
Route::get('/membership/apply', [MembershipApplicationController::class, 'create'])->name('public.membership.apply');
Route::post('/membership/apply', [MembershipApplicationController::class, 'store'])->name('public.membership.store');
Route::get('/forms', [FormDirectoryController::class, 'index'])->name('public.forms.index');
Route::get('/forms/category/{category:slug}', [FormDirectoryController::class, 'category'])->name('public.forms.category');
Route::get('/forms/{onlineForm:slug}', [FormDirectoryController::class, 'show'])->name('public.forms.show');
Route::post('/forms/{onlineForm:slug}', [FormDirectoryController::class, 'store'])->name('public.forms.store');
Route::get('/forms/{onlineForm:slug}/submission/{submission}/next-step', [FormDirectoryController::class, 'nextStep'])->name('public.forms.next-step');
Route::post('/forms/{onlineForm:slug}/submission/{submission}/upload-stamped', [FormDirectoryController::class, 'uploadStamped'])->name('public.forms.upload-stamped');
Route::get('/forms/{onlineForm:slug}/submission/{submission}/print', [FormDirectoryController::class, 'printForSubmission'])->name('public.forms.print-submission');
Route::get('/verify/member/{token}', [MemberVerificationController::class, 'show'])->name('public.member-card.verify');
Route::get('/berita', [NewsController::class, 'index'])->name('public.news.index');
Route::get('/berita/{slug}', [NewsController::class, 'show'])->name('public.news.show');
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{slug}', [NewsController::class, 'show']);
Route::get('/posters', [PosterController::class, 'index'])->name('public.posters.index');

Route::get('/ansuran', [AnsuranCatalogController::class, 'index'])->name('public.ansuran.index');
Route::get('/ansuran/{product:slug}', [AnsuranCatalogController::class, 'show'])->name('public.ansuran.show');

Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '^(?!admin$|member$|api$|login$|register$|dashboard$|storage$|assets$|downloads$|services$|announcements$|perkhidmatan$|pengumuman$|membership$|berita$|news$|forms$|posters$|ansuran$)[A-Za-z0-9-]+$')
    ->name('public.pages.show');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
