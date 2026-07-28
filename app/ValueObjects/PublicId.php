<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Exceptions\InvalidPublicIdException;
use Stringable;

final readonly class PublicId implements Stringable
{
    private const LENGTH = 10;

    private const CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private const PATTERN = '/^[0-9A-Za-z]{10}$/';

    private function __construct(
        private string $value
    ) {}

    /**
     * @throws InvalidPublicIdException
     */
    public static function fromString(string $value): self
    {
        if (! preg_match(self::PATTERN, $value)) {
            throw InvalidPublicIdException::invalidFormat($value);
        }

        return new self($value);
    }

    public static function tryFromString(string $value): ?self
    {
        if (! preg_match(self::PATTERN, $value)) {
            return null;
        }

        return new self($value);
    }

    public static function generate(): self
    {
        $id = '';
        $charsLength = strlen(self::CHARS);

        for ($i = 0; $i < self::LENGTH; $i++) {
            $id .= self::CHARS[random_int(0, $charsLength - 1)];
        }

        return new self($id);
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match(self::PATTERN, $value);
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
