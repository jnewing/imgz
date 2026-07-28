<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Gif = 'gif';

    /**
     * Create a MediaType from a MIME type string.
     */
    public static function fromMimeType(string $mimeType): self
    {
        return match ($mimeType) {
            'image/gif' => self::Gif,
            default => self::Image,
        };
    }

    /**
     * Whether this media type is animated (currently GIF only).
     */
    public function isAnimated(): bool
    {
        return $this === self::Gif;
    }

    /**
     * Whether this is a static or animated image (all current types).
     */
    public function isImage(): bool
    {
        return true;
    }
}
