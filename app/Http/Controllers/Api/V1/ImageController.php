<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\UnsupportedMimeTypeException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Services\ImageStorer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ImageController extends Controller
{
    public function __construct(private readonly ImageStorer $storer) {}

    /**
     * List the authenticated user's images (most recent first).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $images = $request->user()->images()->latest()->paginate(
            perPage: min((int) $request->integer('per_page', 30), 100),
        );

        return ImageResource::collection($images);
    }

    /**
     * Upload an image. Takes a multipart `image` file or a base64 `image` string
     * (the imgbb-style API, with or without a data-URI prefix).
     */
    public function store(Request $request): JsonResponse
    {
        $file = $this->resolveUpload($request);
        $this->assertWithinLimits($file);

        try {
            $image = $this->storer->store($file, $request->user());
        } catch (UnsupportedMimeTypeException $e) {
            throw ValidationException::withMessages(['image' => $e->getUserMessage()]);
        }

        return ImageResource::owned($image)->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    /**
     * Show one of the authenticated user's images.
     */
    public function show(Request $request, Image $image): JsonResponse
    {
        $this->assertOwned($request, $image);

        return ImageResource::owned($image)->response();
    }

    /**
     * Delete one of the authenticated user's images.
     */
    public function destroy(Request $request, Image $image): Response
    {
        $this->assertOwned($request, $image);

        $this->storer->delete($image);

        return response()->noContent();
    }

    /**
     * Resolve the uploaded image into an UploadedFile from either a multipart
     * file or a base64 string.
     *
     * @throws ValidationException
     */
    private function resolveUpload(Request $request): UploadedFile
    {
        if ($request->hasFile('image')) {
            /** @var UploadedFile $file */
            $file = $request->file('image');

            return $file;
        }

        $encoded = $request->input('image');

        if (! is_string($encoded) || $encoded === '') {
            throw ValidationException::withMessages([
                'image' => 'An `image` file or base64 string is required.',
            ]);
        }

        // Strip an optional data-URI prefix (e.g. "data:image/png;base64,").
        if (str_contains($encoded, ',') && str_starts_with($encoded, 'data:')) {
            $encoded = substr($encoded, strpos($encoded, ',') + 1);
        }

        $binary = base64_decode(str_replace(' ', '+', $encoded), true);

        if ($binary === false) {
            throw ValidationException::withMessages([
                'image' => 'The `image` string is not valid base64 data.',
            ]);
        }

        $path = tempnam(sys_get_temp_dir(), 'imgz');
        file_put_contents($path, $binary);

        // test flag so UploadedFile skips is_uploaded_file() on our temp path
        return new UploadedFile($path, 'upload', null, null, true);
    }

    /**
     * @throws ValidationException
     */
    private function assertWithinLimits(UploadedFile $file): void
    {
        $maxBytes = (int) config('imgz.uploads.max_size_kb') * 1024;

        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages([
                'image' => 'The image exceeds the maximum size of '.round($maxBytes / 1048576).' MB.',
            ]);
        }
    }

    private function assertOwned(Request $request, Image $image): void
    {
        abort_unless($image->user_id === $request->user()->id, HttpResponse::HTTP_NOT_FOUND);
    }
}
