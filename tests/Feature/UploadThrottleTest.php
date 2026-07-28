<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('images');
    Queue::fake();
});

it('rate-limits guest uploads by the per-minute burst limit', function () {
    config(['imgz.throttle.guest' => ['per_minute' => 2, 'per_day' => 100]]);

    $upload = fn () => $this->postJson('/upload', [
        'images' => [UploadedFile::fake()->image('p.jpg')],
    ]);

    $upload()->assertCreated();
    $upload()->assertCreated();
    $upload()->assertStatus(429);
});

it('enforces the daily upload count quota for guests', function () {
    config(['imgz.throttle.guest' => ['per_minute' => 100, 'per_day' => 2]]);

    $upload = fn () => $this->postJson('/upload', [
        'images' => [UploadedFile::fake()->image('p.jpg')],
    ]);

    $upload()->assertCreated();
    $upload()->assertCreated();
    $upload()->assertStatus(429);
});

it('rate-limits API uploads by the user limit', function () {
    config(['imgz.throttle.user' => ['per_minute' => 2, 'per_day' => 100]]);
    Sanctum::actingAs(User::factory()->create(), ['images:write']);

    $upload = fn () => $this->postJson('/api/v1/images', [
        'image' => UploadedFile::fake()->image('p.jpg'),
    ]);

    $upload()->assertCreated();
    $upload()->assertCreated();
    $upload()->assertStatus(429);
});
