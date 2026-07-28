<?php

declare(strict_types=1);

use App\Enums\MediaType;
use App\Exceptions\UnsupportedMimeTypeException;
use App\ValueObjects\MimeType;

it('maps supported mime types to extensions', function (string $mime, string $extension) {
    expect(MimeType::fromString($mime)->extension())->toBe($extension);
})->with([
    ['image/jpeg', 'jpg'],
    ['image/png', 'png'],
    ['image/gif', 'gif'],
    ['image/webp', 'webp'],
    ['image/avif', 'avif'],
]);

it('reports supported types', function () {
    expect(MimeType::isSupported('image/png'))->toBeTrue()
        ->and(MimeType::isSupported('video/mp4'))->toBeFalse();
});

it('throws on an unsupported type', function () {
    MimeType::fromString('application/pdf');
})->throws(UnsupportedMimeTypeException::class);

it('returns null from tryFromString on unsupported type', function () {
    expect(MimeType::tryFromString('application/pdf'))->toBeNull();
});

it('resolves the media type', function () {
    expect(MimeType::fromString('image/gif')->mediaType())->toBe(MediaType::Gif)
        ->and(MimeType::fromString('image/png')->mediaType())->toBe(MediaType::Image);
});
