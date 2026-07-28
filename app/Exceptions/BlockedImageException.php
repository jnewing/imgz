<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class BlockedImageException extends ImgzException
{
    public static function blocked(): self
    {
        return new self('This image has been blocked and cannot be uploaded.');
    }

    public function getUserMessage(): string
    {
        return 'This image has been blocked and cannot be uploaded.';
    }

    /**
     * Render as a 422 so the upload endpoints report it like a validation error.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getUserMessage(),
            'errors' => ['image' => [$this->getUserMessage()]],
        ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
