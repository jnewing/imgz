<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Image;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncoderInterface;
use Throwable;

/**
 * Generates a WebP thumbnail for an uploaded image. Best-effort: the original
 * is always served directly, so a failure here is logged rather than fatal.
 */
class GenerateImageVariants implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Image $image) {}

    public function handle(ImageManager $manager): void
    {
        $disk = Storage::disk($this->image->disk);

        if (! $disk->exists($this->image->path)) {
            Log::warning('Cannot generate variants; original missing', [
                'image' => (string) $this->image->public_id,
                'path' => $this->image->path,
            ]);

            return;
        }

        try {
            $thumbnail = $manager->decode($disk->get($this->image->path))
                ->scaleDown(width: (int) config('imgz.variants.thumbnail_width', 400))
                ->encode($this->encoder());

            $disk->put($this->image->thumbnailPath(), (string) $thumbnail);
        } catch (Throwable $e) {
            Log::warning('Thumbnail generation failed', [
                'image' => (string) $this->image->public_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve the encoder for the configured thumbnail format.
     */
    private function encoder(): EncoderInterface
    {
        $quality = (int) config('imgz.variants.thumbnail_quality', 80);

        return match (strtolower((string) config('imgz.variants.thumbnail_format', 'webp'))) {
            'jpg', 'jpeg' => new JpegEncoder(quality: $quality),
            'png' => new PngEncoder,
            default => new WebpEncoder(quality: $quality),
        };
    }
}
