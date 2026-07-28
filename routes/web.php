<?php

use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FilehostController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ImageReportController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('docs', [DocsController::class, 'index'])->name('docs');
Route::inertia('terms', 'legal/Terms')->name('terms');
Route::inertia('acceptable-use', 'legal/AcceptableUse')->name('aup');
Route::inertia('privacy', 'legal/Privacy')->name('privacy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Admin moderation.
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('moderation', [ModerationController::class, 'index'])->name('moderation');
    Route::delete('moderation/{image}', [ModerationController::class, 'remove'])
        ->where('image', '[0-9A-Za-z]{10}')
        ->name('moderation.remove');
    Route::post('moderation/{image}/dismiss', [ModerationController::class, 'dismiss'])
        ->where('image', '[0-9A-Za-z]{10}')
        ->name('moderation.dismiss');
});

// Uploads & image pages.
Route::post('upload', [UploadController::class, 'store'])
    ->middleware('throttle:uploads')
    ->name('upload.store');

// IRCv3 FILEHOST upload endpoint. Point an IRC server's `draft/FILEHOST`
// ISUPPORT token (or a client adapter) at this URL. Optional bearer token
// attributes the upload; anonymous uploads follow the guest-upload config.
Route::post('filehost', [FilehostController::class, 'store'])
    ->middleware(['sanctum.optional', 'throttle:uploads'])
    ->name('filehost.store');
Route::options('filehost', [FilehostController::class, 'options'])
    ->name('filehost.options');

// Direct file serving by clean filename, e.g. /i/aB3dE5gH7j.png or
// /i/aB3dE5gH7j_thumb.webp. Declared before the viewer route and constrained to
// an extension so it only matches file requests. In production the reverse
// proxy / Caddy serves these statically; this is the local-dev fallback.
Route::get('i/{file}', [FileController::class, 'show'])
    ->where('file', '[0-9A-Za-z]{10}(_thumb)?\.[a-z0-9]{3,4}')
    ->name('files.show');

Route::post('i/{image}/report', [ImageReportController::class, 'store'])
    ->where('image', '[0-9A-Za-z]{10}')
    ->middleware('throttle:6,1')
    ->name('images.report');

Route::get('i/{image}/delete/{token}', [ImageController::class, 'confirmDelete'])
    ->where('image', '[0-9A-Za-z]{10}')
    ->name('images.confirm-delete');
Route::delete('i/{image}', [ImageController::class, 'destroy'])
    ->where('image', '[0-9A-Za-z]{10}')
    ->name('images.destroy');
Route::get('i/{image}', [ImageController::class, 'show'])
    ->where('image', '[0-9A-Za-z]{10}')
    ->name('images.show');

require __DIR__.'/settings.php';
