<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\BlockedImageException;
use App\Exceptions\UnsupportedMimeTypeException;
use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Services\ImageStorer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Zipline-compatible upload API. Zipline (https://github.com/diced/zipline) is a
 * self-hosted ShareX-style host that IRC clients like Lurker can target as a
 * configurable uploader. Speaking its small contract lets those clients point at
 * imgz with no changes: pick the "Zipline" uploader, enter the URL and an
 * images:write token. The token comes in raw (see NormalizeBearerToken), so these
 * routes use the usual auth:sanctum + abilities middleware.
 */
class ZiplineUploadController extends Controller
{
    public function __construct(private readonly ImageStorer $storer) {}

    /**
     * Upload a file and respond with Zipline's `{ files: [{ url, id }] }` shape.
     */
    public function store(Request $request): JsonResponse
    {
        $file = $request->file('file');

        // Zipline sends a single `file` part; tolerate an array just in case.
        if (is_array($file)) {
            $file = $file[0] ?? null;
        }

        if (! $file instanceof UploadedFile) {
            return response()->json(['error' => 'A `file` upload is required.'], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $maxBytes = (int) config('imgz.uploads.max_size_kb') * 1024;

        if (($file->getSize() ?: 0) > $maxBytes) {
            return response()->json(
                ['error' => 'The file exceeds the maximum size of '.round($maxBytes / 1048576).' MB.'],
                HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $image = $this->storer->store($file, $request->user());
        } catch (UnsupportedMimeTypeException $e) {
            return response()->json(['error' => $e->getUserMessage()], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (BlockedImageException) {
            return response()->json(['error' => 'This image has been blocked and cannot be uploaded.'], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'files' => [
                [
                    'url' => $image->direct_url,
                    'id' => (string) $image->public_id,
                ],
            ],
        ]);
    }

    /**
     * Delete a file by the id from the upload response (Zipline's
     * DELETE /api/user/files/{id}). A missing or unowned file returns 404, which
     * clients treat as already gone.
     */
    public function destroy(Request $request, Image $image): Response
    {
        abort_unless($image->user_id === $request->user()->id, HttpResponse::HTTP_NOT_FOUND);

        $this->storer->delete($image);

        return response()->noContent();
    }
}
