<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

abstract class ImgzException extends Exception
{
    /**
     * Get a user-friendly error message suitable for display.
     */
    abstract public function getUserMessage(): string;
}
