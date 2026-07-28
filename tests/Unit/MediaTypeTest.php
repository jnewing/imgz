<?php

declare(strict_types=1);

use App\Enums\MediaType;

it('resolves from mime type', function () {
    expect(MediaType::fromMimeType('image/gif'))->toBe(MediaType::Gif)
        ->and(MediaType::fromMimeType('image/jpeg'))->toBe(MediaType::Image)
        ->and(MediaType::fromMimeType('image/webp'))->toBe(MediaType::Image);
});

it('reports animation', function () {
    expect(MediaType::Gif->isAnimated())->toBeTrue()
        ->and(MediaType::Image->isAnimated())->toBeFalse();
});
