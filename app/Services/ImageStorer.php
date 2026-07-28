<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BlockedImageException;
use App\Exceptions\FileStorageException;
use App\Exceptions\UnsupportedMimeTypeException;
use App\Jobs\GenerateImageVariants;
use App\Models\BlockedChecksum;
use App\Models\Image;
use App\Models\User;
use App\ValueObjects\MimeType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * One place where an uploaded file becomes a stored image. Path generation,
 * mime and dimension detection, and record creation all live here.
 */
class ImageStorer
{
    /**
     * Store an uploaded file and create its Image record.
     *
     * @param  array{title?: ?string, description?: ?string, expires_at?: ?\DateTimeInterface}  $options
     *
     * @throws UnsupportedMimeTypeException
     * @throws BlockedImageException
     * @throws FileStorageException
     */
    public function store(UploadedFile $file, ?User $uploader = null, array $options = []): Image
    {
        $mimeType = $this->resolveMimeType($file);

        $realPath = $file->getRealPath();
        if ($realPath === false) {
            throw FileStorageException::failedToRead($file->getClientOriginalName());
        }

        // don't let removed content back in before we write anything
        $checksum = hash_file('sha256', $realPath) ?: null;
        if (BlockedChecksum::isBlocked($checksum)) {
            throw BlockedImageException::blocked();
        }

        // shard by public id so the on-disk path maps straight to the static URL
        $publicId = Image::uniquePublicId();
        $filename = "{$publicId}.{$mimeType->extension()}";
        $path = $this->shardedPath((string) $publicId, $filename);
        $disk = $this->disk();

        $stored = Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));
        if ($stored === false) {
            throw FileStorageException::failedToStore($path);
        }

        [$width, $height] = $this->dimensions($realPath);

        $image = Image::create([
            'user_id' => $uploader?->id,
            'public_id' => (string) $publicId,
            'disk' => $disk,
            'path' => $path,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => (string) $mimeType,
            'media_type' => $mimeType->mediaType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'checksum' => $checksum,
            'title' => $options['title'] ?? null,
            'description' => $options['description'] ?? null,
            'expires_at' => $options['expires_at'] ?? null,
        ]);

        GenerateImageVariants::dispatch($image);

        return $image;
    }

    public function delete(Image $image): bool
    {
        $disk = Storage::disk($image->disk);

        foreach ([$image->path, $image->thumbnailPath()] as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        return $image->forceDelete();
    }

    private function disk(): string
    {
        return config('imgz.storage_disk', 'images');
    }

    /**
     * @throws UnsupportedMimeTypeException
     */
    private function resolveMimeType(UploadedFile $file): MimeType
    {
        $detected = $file->getMimeType();

        /** @var string[] $allowed */
        $allowed = config('imgz.uploads.allowed_mimes', MimeType::allowedTypes());

        if ($detected === null || ! MimeType::isSupported($detected) || ! in_array($detected, $allowed, true)) {
            throw UnsupportedMimeTypeException::notAllowed($detected ?? 'unknown', $allowed);
        }

        return MimeType::fromString($detected);
    }

    /**
     * Shard the path by the first four chars of the id so no one directory
     * grows huge, e.g. aB/3d/aB3dE5gH7j.jpg.
     */
    private function shardedPath(string $publicId, string $filename): string
    {
        $dir1 = substr($publicId, 0, 2);
        $dir2 = substr($publicId, 2, 2);

        return "{$dir1}/{$dir2}/{$filename}";
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function dimensions(string $realPath): array
    {
        $size = @getimagesize($realPath);

        if ($size === false) {
            Log::warning('Could not determine image dimensions', ['path' => $realPath]);

            return [null, null];
        }

        return [$size[0], $size[1]];
    }
}
