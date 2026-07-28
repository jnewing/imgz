<?php

declare(strict_types=1);

use App\Exceptions\BlockedImageException;
use App\Models\BlockedChecksum;
use App\Models\Image;
use App\Services\ImageStorer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('images');
    Queue::fake();
});

/**
 * Create a PNG with fixed bytes and return [path, sha256].
 *
 * @return array{0: string, 1: string}
 */
function blockedPng(): array
{
    $path = tempnam(sys_get_temp_dir(), 'imgz').'.png';
    $image = imagecreatetruecolor(24, 24);
    imagepng($image, $path);
    imagedestroy($image);

    return [$path, hash_file('sha256', $path)];
}

it('rejects storing a blocked checksum', function () {
    [$path, $checksum] = blockedPng();
    BlockedChecksum::create(['checksum' => $checksum, 'reason' => 'abuse']);

    app(ImageStorer::class)->store(new UploadedFile($path, 'x.png', 'image/png', null, true));
})->throws(BlockedImageException::class);

it('returns 422 and stores nothing for a blocked image over HTTP', function () {
    [$path, $checksum] = blockedPng();
    BlockedChecksum::create(['checksum' => $checksum]);

    $this->postJson('/upload', [
        'images' => [new UploadedFile($path, 'x.png', 'image/png', null, true)],
    ])->assertStatus(422);

    expect(Image::count())->toBe(0);
});

it('allows an image whose checksum is not blocked', function () {
    $this->postJson('/upload', [
        'images' => [UploadedFile::fake()->image('ok.png')],
    ])->assertCreated();
});
