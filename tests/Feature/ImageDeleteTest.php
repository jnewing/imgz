<?php

declare(strict_types=1);

use App\Models\Image;
use App\Models\User;
use App\Services\ImageStorer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('images');
    $this->storer = app(ImageStorer::class);
});

it('lets a guest delete their upload with the delete token', function () {
    $image = $this->storer->store(UploadedFile::fake()->image('photo.jpg'));

    $this->deleteJson("/i/{$image->public_id}", ['token' => $image->delete_token])
        ->assertOk()
        ->assertJson(['deleted' => true]);

    expect(Image::withTrashed()->count())->toBe(0);
    Storage::disk('images')->assertMissing($image->path);
});

it('rejects deletion with the wrong token', function () {
    $image = $this->storer->store(UploadedFile::fake()->image('photo.jpg'));

    $this->deleteJson("/i/{$image->public_id}", ['token' => 'nope'])->assertForbidden();

    expect(Image::count())->toBe(1);
    Storage::disk('images')->assertExists($image->path);
});

it('lets the owner delete their image', function () {
    $user = User::factory()->create();
    $image = $this->storer->store(UploadedFile::fake()->image('photo.jpg'), $user);

    $this->actingAs($user)->deleteJson("/i/{$image->public_id}")->assertOk();

    expect(Image::count())->toBe(0);
});

it('forbids another user from deleting an image without the token', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $image = $this->storer->store(UploadedFile::fake()->image('photo.jpg'), $owner);

    $this->actingAs($other)->deleteJson("/i/{$image->public_id}")->assertForbidden();

    expect(Image::count())->toBe(1);
});
