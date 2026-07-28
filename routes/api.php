<?php

use App\Http\Controllers\Api\V1\ImageController;
use App\Http\Controllers\Api\ZiplineUploadController;
use Illuminate\Support\Facades\Route;

// Zipline-compatible upload API. Lets IRC clients that support a self-hosted
// Zipline uploader (e.g. Lurker) target imgz with no client changes. Clients
// send the API token raw in the Authorization header; the global
// NormalizeBearerToken middleware adapts it into a bearer token for Sanctum.
Route::post('upload', [ZiplineUploadController::class, 'store'])
    ->middleware(['auth:sanctum', 'abilities:images:write', 'throttle:uploads'])
    ->name('api.upload');

Route::delete('user/files/{image}', [ZiplineUploadController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'abilities:images:delete'])
    ->name('api.user.files.destroy');

Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->name('api.v1.')
    ->group(function () {
        Route::get('images', [ImageController::class, 'index'])
            ->middleware('abilities:images:read')
            ->name('images.index');

        Route::post('images', [ImageController::class, 'store'])
            ->middleware(['abilities:images:write', 'throttle:uploads'])
            ->name('images.store');

        Route::get('images/{image}', [ImageController::class, 'show'])
            ->middleware('abilities:images:read')
            ->name('images.show');

        Route::delete('images/{image}', [ImageController::class, 'destroy'])
            ->middleware('abilities:images:delete')
            ->name('images.destroy');
    });
