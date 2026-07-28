<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\MediaType;
use App\Exceptions\UnsupportedMimeTypeException;
use Stringable;

/**
 * The media types imgz understands, mapped to their file extension. What's
 * actually accepted at upload time is narrowed by config('imgz.uploads.allowed_mimes').
 */
final readonly class MimeType implements Stringable
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    private function __construct(
        private string $value
    ) {}

    /**
     * @throws UnsupportedMimeTypeException
     */
    public static function fromString(string $value): self
    {
        if (! isset(self::ALLOWED[$value])) {
            throw UnsupportedMimeTypeException::notAllowed($value, array_keys(self::ALLOWED));
        }

        return new self($value);
    }

    public static function tryFromString(string $value): ?self
    {
        if (! isset(self::ALLOWED[$value])) {
            return null;
        }

        return new self($value);
    }

    public static function isSupported(string $value): bool
    {
        return isset(self::ALLOWED[$value]);
    }

    /**
     * @return string[]
     */
    public static function allowedTypes(): array
    {
        return array_keys(self::ALLOWED);
    }

    public function extension(): string
    {
        return self::ALLOWED[$this->value];
    }

    public function mediaType(): MediaType
    {
        return MediaType::fromMimeType($this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
