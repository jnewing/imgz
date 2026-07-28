<?php

declare(strict_types=1);

namespace App\Exceptions;

final class FileStorageException extends ImgzException
{
    public static function failedToRead(string $path): self
    {
        return new self("Failed to read file at: {$path}");
    }

    public static function failedToStore(string $path): self
    {
        return new self("Failed to store file at: {$path}");
    }

    public static function notFound(string $path): self
    {
        return new self("File not found: {$path}");
    }

    public function getUserMessage(): string
    {
        return 'A file storage error occurred. Please try again.';
    }
}
