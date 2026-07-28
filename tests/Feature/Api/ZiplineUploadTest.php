<?php

declare(strict_types=1);

use App\Models\Image;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('images');
    Queue::fake();
});

/**
 * Create a real token and return its plaintext, as a client would hold it.
 */
function imgzToken(User $user, array $abilities = ['images:write']): string
{
    return $user->createToken('lurker', $abilities)->plainTextToken;
}

it('uploads with a raw (non-Bearer) token and returns the Zipline shape', function () {
    $user = User::factory()->create();

    $response = $this->withHeader('Authorization', imgzToken($user))
        ->post('/api/upload', ['file' => UploadedFile::fake()->image('cat.png', 48, 48)]);

    $response->assertOk();

    $image = Image::firstOrFail();

    expect($image->user_id)->toBe($user->id);
    $response->assertJsonPath('files.0.id', (string) $image->public_id)
        ->assertJsonPath('files.0.url', $image->direct_url);
    expect($response->json('files.0.url'))->toContain("/i/{$image->public_id}.png");
    Storage::disk('images')->assertExists($image->path);
});

it('also accepts a standard Bearer token', function () {
    $user = User::factory()->create();

    $this->withHeader('Authorization', 'Bearer '.imgzToken($user))
        ->post('/api/upload', ['file' => UploadedFile::fake()->image('a.png')])
        ->assertOk();
});

it('rejects an upload without a token', function () {
    $this->postJson('/api/upload', ['file' => UploadedFile::fake()->image('a.png')])
        ->assertUnauthorized();
});

it('forbids an upload with a read-only token', function () {
    $user = User::factory()->create();

    $this->withHeader('Authorization', imgzToken($user, ['images:read']))
        ->post('/api/upload', ['file' => UploadedFile::fake()->image('a.png')])
        ->assertForbidden();
});

it('requires a file part', function () {
    $user = User::factory()->create();

    $this->withHeader('Authorization', imgzToken($user))
        ->postJson('/api/upload', [])
        ->assertStatus(422);
});

it('rejects a file over the size limit', function () {
    config(['imgz.uploads.max_size_kb' => 100]);
    $user = User::factory()->create();

    $this->withHeader('Authorization', imgzToken($user))
        ->post('/api/upload', ['file' => UploadedFile::fake()->image('big.png')->size(5000)])
        ->assertStatus(422);
});

it('rejects an unsupported file type', function () {
    $user = User::factory()->create();

    $this->withHeader('Authorization', imgzToken($user))
        ->post('/api/upload', ['file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')])
        ->assertStatus(422);
});

it('deletes an owned file by its id with a delete token', function () {
    $user = User::factory()->create();
    $image = Image::factory()->for($user)->create();

    $this->withHeader('Authorization', imgzToken($user, ['images:delete']))
        ->delete("/api/user/files/{$image->public_id}")
        ->assertNoContent();

    expect(Image::withTrashed()->count())->toBe(0);
});

it('returns 404 when deleting a file owned by someone else', function () {
    $user = User::factory()->create();
    $other = Image::factory()->create();

    $this->withHeader('Authorization', imgzToken($user, ['images:delete']))
        ->delete("/api/user/files/{$other->public_id}")
        ->assertNotFound();
});
