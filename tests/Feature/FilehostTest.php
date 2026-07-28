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

/**
 * POST raw bytes to the FILEHOST endpoint the way an IRC client would.
 */
function filehostUpload(string $content, string $contentType = 'image/png', array $server = [])
{
    return test()->call('POST', '/filehost', [], [], [], array_merge([
        'CONTENT_TYPE' => $contentType,
        'HTTP_CONTENT_DISPOSITION' => 'attachment; filename="cat.png"',
    ], $server), $content);
}

function fakePngBytes(int $width = 32, int $height = 32): string
{
    // keep the fake file in a var so its temp resource lives until we read it
    $file = UploadedFile::fake()->image('cat.png', $width, $height);

    return file_get_contents($file->getRealPath());
}

it('accepts a raw-body guest upload and returns the URL as Location and body', function () {
    $response = filehostUpload(fakePngBytes());

    $response->assertCreated();

    $image = Image::firstOrFail();

    expect($image->user_id)->toBeNull();
    expect($response->headers->get('Location'))->toBe($image->direct_url);
    expect(trim($response->getContent()))->toBe($image->direct_url);
    expect($image->direct_url)->toContain("/i/{$image->public_id}.png");
    Storage::disk('images')->assertExists($image->path);
});

it('attributes the upload to a bearer token owner', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['images:write']);

    filehostUpload(fakePngBytes())->assertCreated();

    expect(Image::firstOrFail()->user_id)->toBe($user->id);
});

it('forbids a token without the write ability', function () {
    Sanctum::actingAs(User::factory()->create(), ['images:read']);

    filehostUpload(fakePngBytes())->assertForbidden();
});

it('rejects a guest upload when guests are not permitted', function () {
    config(['imgz.features.guest_uploads' => false]);

    filehostUpload(fakePngBytes())->assertUnauthorized();
});

it('accepts a multipart file too', function () {
    $this->post('/filehost', ['file' => UploadedFile::fake()->image('a.png', 20, 20)])
        ->assertCreated();

    expect(Image::count())->toBe(1);
});

it('rejects an empty body', function () {
    filehostUpload('')->assertStatus(422);
});

it('rejects a body over the size limit', function () {
    config(['imgz.uploads.max_size_kb' => 1]);

    filehostUpload(str_repeat('x', 2048))->assertStatus(422);
});

it('rejects an unsupported file type', function () {
    filehostUpload('this is plainly not an image', 'text/plain')
        ->assertStatus(422);
});

it('advertises accepted types on an OPTIONS preflight', function () {
    $response = $this->call('OPTIONS', '/filehost');

    $response->assertNoContent();
    expect($response->headers->get('Accept-Post'))->toContain('image/png');
});

it('applies the upload rate limit', function () {
    config(['imgz.throttle.guest.per_minute' => 1]);

    filehostUpload(fakePngBytes())->assertCreated();
    filehostUpload(fakePngBytes())->assertStatus(429);
});

/**
 * Build an HTTP Basic Authorization header, as an IRC client sends when a
 * FILEHOST upload uses SASL PLAIN credentials (e.g. Halloy's credentials.plain).
 */
function basicAuth(string $username, string $password): array
{
    return ['HTTP_AUTHORIZATION' => 'Basic '.base64_encode("{$username}:{$password}")];
}

it('attributes a Basic-auth upload with the token in the password', function () {
    $user = User::factory()->create();
    $token = $user->createToken('halloy', ['images:write'])->plainTextToken;

    filehostUpload(fakePngBytes(), 'image/png', basicAuth('syn', $token))->assertCreated();

    expect(Image::firstOrFail()->user_id)->toBe($user->id);
});

it('attributes a Basic-auth upload with the token in the username', function () {
    $user = User::factory()->create();
    $token = $user->createToken('halloy', ['images:write'])->plainTextToken;

    filehostUpload(fakePngBytes(), 'image/png', basicAuth($token, 'x'))->assertCreated();

    expect(Image::firstOrFail()->user_id)->toBe($user->id);
});

it('forbids a Basic-auth upload with a read-only token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('halloy', ['images:read'])->plainTextToken;

    filehostUpload(fakePngBytes(), 'image/png', basicAuth('syn', $token))->assertForbidden();
});

it('falls back to an anonymous upload when Basic credentials are not a token', function () {
    filehostUpload(fakePngBytes(), 'image/png', basicAuth('someuser', 'hunter2'))
        ->assertCreated();

    expect(Image::firstOrFail()->user_id)->toBeNull();
});
