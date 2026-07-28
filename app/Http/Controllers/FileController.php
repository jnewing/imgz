<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class FileController extends Controller
{
    /**
     * Serve a stored image (or thumbnail) by its clean filename, e.g.
     * /i/aB3dE5gH7j.png or /i/aB3dE5gH7j_thumb.webp. The storage shard is
     * derived from the leading id characters, so the on-disk path never
     * appears in the URL.
     *
     * In production the reverse proxy / Caddy serves these directly from the
     * images directory and never reaches PHP; this route is the local-dev and
     * non-CDN self-host fallback.
     */
    public function show(string $file): BinaryFileResponse
    {
        $disk = Storage::disk(config('imgz.storage_disk', 'images'));
        $path = substr($file, 0, 2).'/'.substr($file, 2, 2)."/{$file}";

        abort_unless($disk->exists($path), HttpResponse::HTTP_NOT_FOUND);

        return response()->file($disk->path($path), [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
