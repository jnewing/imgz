<?php

declare(strict_types=1);

use App\Jobs\GenerateImageVariants;
use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

beforeEach(function () {
    Storage::fake('images');
});

function storeOriginal(string $publicId = 'aB3dE5gH7j', int $width = 1200, int $height = 900): Image
{
    $path = substr($publicId, 0, 2).'/'.substr($publicId, 2, 2)."/{$publicId}.jpg";

    Storage::disk('images')->putFileAs(
        dirname($path),
        UploadedFile::fake()->image('original.jpg', $width, $height),
        basename($path),
    );

    return Image::factory()->create([
        'public_id' => $publicId,
        'path' => $path,
        'filename' => "{$publicId}.jpg",
        'disk' => 'images',
        'mime_type' => 'image/jpeg',
        'width' => $width,
        'height' => $height,
    ]);
}

it('generates a downscaled webp thumbnail', function () {
    $image = storeOriginal(width: 1200, height: 900);

    (new GenerateImageVariants($image))->handle(app(ImageManager::class));

    Storage::disk('images')->assertExists($image->thumbnailPath());

    $thumb = app(ImageManager::class)->decode(Storage::disk('images')->get($image->thumbnailPath()));

    expect($thumb->width())->toBe(config('imgz.variants.thumbnail_width'))
        ->and($thumb->height())->toBe(300); // 1200x900 scaled to 400 wide
});

it('does not upscale smaller images', function () {
    $image = storeOriginal(width: 200, height: 150);

    (new GenerateImageVariants($image))->handle(app(ImageManager::class));

    $thumb = app(ImageManager::class)->decode(Storage::disk('images')->get($image->thumbnailPath()));

    expect($thumb->width())->toBe(200);
});

it('handles a missing original gracefully', function () {
    $image = Image::factory()->create(['path' => 'aB/3d/missing.jpg']);

    (new GenerateImageVariants($image))->handle(app(ImageManager::class));

    Storage::disk('images')->assertMissing($image->thumbnailPath());
});
