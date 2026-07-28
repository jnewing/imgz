<?php

declare(strict_types=1);

namespace App\Exceptions;

final class InvalidPublicIdException extends ImgzException
{
    private function __construct(
        string $message,
        private readonly string $invalidValue
    ) {
        parent::__construct($message);
    }

    public static function invalidFormat(string $value): self
    {
        return new self(
            "Invalid public ID format: '{$value}'. Must be 10 alphanumeric characters.",
            $value
        );
    }

    public function getInvalidValue(): string
    {
        return $this->invalidValue;
    }

    public function getUserMessage(): string
    {
        return 'The requested resource could not be found.';
    }
}
