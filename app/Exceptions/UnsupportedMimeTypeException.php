<?php

declare(strict_types=1);

namespace App\Exceptions;

final class UnsupportedMimeTypeException extends ImgzException
{
    /**
     * @param  string[]  $allowed
     */
    public static function notAllowed(string $mimeType, array $allowed): self
    {
        $allowedStr = implode(', ', $allowed);

        return new self("Unsupported file type: {$mimeType}. Allowed: {$allowedStr}");
    }

    public function getUserMessage(): string
    {
        return 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP, AVIF.';
    }
}
