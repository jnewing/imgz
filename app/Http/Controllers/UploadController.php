<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use App\Http\Resources\ImageResource;
use App\Services\ImageStorer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class UploadController extends Controller
{
    public function __construct(private readonly ImageStorer $storer) {}

    /**
     * Store one or more uploaded images and return their share/delete links.
     */
    public function store(StoreImageRequest $request): JsonResponse
    {
        $options = [
            'title' => $request->input('title'),
            'expires_at' => $this->resolveExpiry($request->integer('expires_in')),
        ];

        /** @var UploadedFile[] $files */
        $files = $request->file('images');

        $images = collect($files)->map(
            fn (UploadedFile $file): ImageResource => ImageResource::owned(
                $this->storer->store($file, $request->user(), $options)
            )
        );

        return response()->json(['images' => $images], 201);
    }

    /**
     * Resolve an absolute expiry time from a requested minutes-from-now value,
     * falling back to the configured default retention.
     */
    private function resolveExpiry(int $minutes): ?\DateTimeInterface
    {
        if ($minutes > 0) {
            return now()->addMinutes($minutes);
        }

        $default = config('imgz.retention.default_minutes');

        return $default !== null ? now()->addMinutes((int) $default) : null;
    }
}
