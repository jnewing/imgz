<?php

declare(strict_types=1);

use App\Models\Image;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('images');
});

it('lets a guest upload an image and returns share + delete links', function () {
    $response = $this->postJson('/upload', [
        'images' => [UploadedFile::fake()->image('photo.jpg', 800, 600)],
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'images' => [['public_id', 'url', 'direct_url', 'thumbnail_url', 'delete_url', 'width', 'height']],
        ]);

    $image = Image::firstOrFail();

    expect($image->user_id)->toBeNull();
    Storage::disk('images')->assertExists($image->path);
    expect($response->json('images.0.direct_url'))->toContain("/i/{$image->public_id}.")
        ->and($response->json('images.0.delete_url'))->toContain($image->delete_token);
});

it('records the authenticated uploader', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/upload', [
        'images' => [UploadedFile::fake()->image('photo.jpg')],
    ])->assertCreated();

    expect(Image::firstOrFail()->user_id)->toBe($user->id);
});

it('stores multiple files in one request', function () {
    $this->postJson('/upload', [
        'images' => [
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.png'),
        ],
    ])->assertCreated()->assertJsonCount(2, 'images');

    expect(Image::count())->toBe(2);
});

it('applies the expiry option', function () {
    $this->postJson('/upload', [
        'images' => [UploadedFile::fake()->image('photo.jpg')],
        'expires_in' => 60,
    ])->assertCreated();

    expect(Image::firstOrFail()->expires_at)->not->toBeNull();
});

it('requires at least one image', function () {
    $this->postJson('/upload', [])->assertStatus(422);
});

it('rejects unsupported file types', function () {
    $this->postJson('/upload', [
        'images' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
    ])->assertStatus(422);
});

it('rejects too many files', function () {
    config(['imgz.uploads.max_files' => 1]);

    $this->postJson('/upload', [
        'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
    ])->assertStatus(422);
});

it('rejects files over the size limit', function () {
    config(['imgz.uploads.max_size_kb' => 100]);

    $this->postJson('/upload', [
        'images' => [UploadedFile::fake()->image('big.jpg')->size(5000)],
    ])->assertStatus(422);
});

it('forbids guest uploads when disabled', function () {
    config(['imgz.features.guest_uploads' => false]);

    $this->postJson('/upload', [
        'images' => [UploadedFile::fake()->image('photo.jpg')],
    ])->assertStatus(403);
});

it('forbids guest uploads when an account is required', function () {
    config(['imgz.features.require_account' => true]);

    $this->postJson('/upload', [
        'images' => [UploadedFile::fake()->image('photo.jpg')],
    ])->assertStatus(403);
});
