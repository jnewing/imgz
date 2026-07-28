<?php

declare(strict_types=1);

use App\Models\Image;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('images');
    Queue::fake();
});

it('requires authentication', function () {
    $this->postJson('/api/v1/images', [
        'image' => UploadedFile::fake()->image('photo.jpg'),
    ])->assertUnauthorized();
});

it('uploads an image with a write token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['images:write']);

    $response = $this->postJson('/api/v1/images', [
        'image' => UploadedFile::fake()->image('photo.jpg', 640, 480),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.width', 640)
        ->assertJsonStructure(['data' => ['public_id', 'url', 'direct_url', 'delete_url']]);

    $image = Image::firstOrFail();
    expect($image->user_id)->toBe($user->id);
    Storage::disk('images')->assertExists($image->path);
});

it('uploads an image from a base64 string', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['images:write']);

    $file = UploadedFile::fake()->image('photo.png', 32, 32);
    $base64 = base64_encode(file_get_contents($file->getRealPath()));

    $this->postJson('/api/v1/images', ['image' => "data:image/png;base64,{$base64}"])
        ->assertCreated();

    expect(Image::count())->toBe(1);
});

it('forbids upload without the write ability', function () {
    Sanctum::actingAs(User::factory()->create(), ['images:read']);

    $this->postJson('/api/v1/images', [
        'image' => UploadedFile::fake()->image('photo.jpg'),
    ])->assertForbidden();
});

it('rejects an unsupported type', function () {
    Sanctum::actingAs(User::factory()->create(), ['images:write']);

    $this->postJson('/api/v1/images', [
        'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
    ])->assertStatus(422);
});

it('rejects a file over the size limit', function () {
    config(['imgz.uploads.max_size_kb' => 100]);
    Sanctum::actingAs(User::factory()->create(), ['images:write']);

    $this->postJson('/api/v1/images', [
        'image' => UploadedFile::fake()->image('big.jpg')->size(5000),
    ])->assertStatus(422);
});

it('lists only the user own images with a read token', function () {
    $user = User::factory()->create();
    Image::factory()->count(2)->for($user)->create();
    Image::factory()->create(); // someone else's

    Sanctum::actingAs($user, ['images:read']);

    $this->getJson('/api/v1/images')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('shows an owned image but hides others', function () {
    $user = User::factory()->create();
    $own = Image::factory()->for($user)->create();
    $other = Image::factory()->create();

    Sanctum::actingAs($user, ['images:read']);

    $this->getJson("/api/v1/images/{$own->public_id}")->assertOk();
    $this->getJson("/api/v1/images/{$other->public_id}")->assertNotFound();
});

it('deletes an owned image with a delete token', function () {
    $user = User::factory()->create();
    $image = Image::factory()->for($user)->create();

    Sanctum::actingAs($user, ['images:delete']);

    $this->deleteJson("/api/v1/images/{$image->public_id}")->assertNoContent();
    expect(Image::withTrashed()->count())->toBe(0);
});

it('forbids delete without the delete ability', function () {
    $user = User::factory()->create();
    $image = Image::factory()->for($user)->create();

    Sanctum::actingAs($user, ['images:read']);

    $this->deleteJson("/api/v1/images/{$image->public_id}")->assertForbidden();
});
