<?php

declare(strict_types=1);

use App\Enums\MediaType;
use App\Exceptions\UnsupportedMimeTypeException;
use App\Jobs\GenerateImageVariants;
use App\Models\Image;
use App\Models\User;
use App\Services\ImageStorer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('images');
    Queue::fake();
    $this->storer = app(ImageStorer::class);
});

it('dispatches thumbnail generation after storing', function () {
    $this->storer->store(UploadedFile::fake()->image('photo.jpg'));

    Queue::assertPushed(GenerateImageVariants::class);
});

it('stores an uploaded image and creates a record', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

    $image = $this->storer->store($file);

    expect($image)->toBeInstanceOf(Image::class)
        ->and($image->media_type)->toBe(MediaType::Image)
        ->and($image->mime_type)->toBe('image/jpeg')
        ->and($image->width)->toBe(800)
        ->and($image->height)->toBe(600)
        ->and($image->disk)->toBe('images')
        ->and($image->checksum)->toHaveLength(64);

    Storage::disk('images')->assertExists($image->path);
});

it('shards the storage path by public id', function () {
    $image = $this->storer->store(UploadedFile::fake()->image('photo.png'));

    $publicId = (string) $image->public_id;

    expect($image->path)->toBe(substr($publicId, 0, 2).'/'.substr($publicId, 2, 2)."/{$publicId}.png")
        ->and($image->filename)->toBe("{$publicId}.png");
});

it('generates a public id and delete token', function () {
    $image = $this->storer->store(UploadedFile::fake()->image('photo.jpg'));

    expect((string) $image->public_id)->toMatch('/^[0-9A-Za-z]{10}$/')
        ->and($image->delete_token)->toHaveLength(64);
});

it('records the uploader when provided', function () {
    $user = User::factory()->create();

    $image = $this->storer->store(UploadedFile::fake()->image('photo.jpg'), $user);

    expect($image->user_id)->toBe($user->id)
        ->and($image->isAnonymous())->toBeFalse();
});

it('stores anonymously when no uploader is given', function () {
    $image = $this->storer->store(UploadedFile::fake()->image('photo.jpg'));

    expect($image->user_id)->toBeNull()
        ->and($image->isAnonymous())->toBeTrue();
});

it('applies upload options', function () {
    $expiry = now()->addDay();

    $image = $this->storer->store(UploadedFile::fake()->image('photo.jpg'), null, [
        'title' => 'Sunset',
        'expires_at' => $expiry,
    ]);

    expect($image->title)->toBe('Sunset')
        ->and($image->expires_at->timestamp)->toBe($expiry->timestamp);
});

it('rejects unsupported file types', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->storer->store($file);
})->throws(UnsupportedMimeTypeException::class);
