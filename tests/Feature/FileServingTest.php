<?php

declare(strict_types=1);

use App\Services\ImageStorer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('images');
    $this->storer = app(ImageStorer::class);
});

it('serves a stored file by its clean filename with long-lived cache headers', function () {
    $image = $this->storer->store(UploadedFile::fake()->image('photo.jpg'));

    $response = $this->get("/i/{$image->filename}");

    $response->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('max-age=31536000');
});

it('derives the storage shard from the id, not the url', function () {
    $image = $this->storer->store(UploadedFile::fake()->image('photo.png'));

    // The URL exposes only the flat filename; the sharded path is not present.
    expect($image->direct_url)->toContain("/i/{$image->public_id}.png")
        ->and($image->direct_url)->not->toContain($image->path);
});

it('returns 404 for a missing file', function () {
    $this->get('/i/AAAAAAAAAA.png')->assertNotFound();
});

it('does not treat a malformed filename as a file route', function () {
    // Fails the filename constraint, so it is not served as a file.
    $this->get('/i/not-a-valid-id')->assertNotFound();
});
