<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ApiAbility;
use App\ValueObjects\MimeType;
use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    /**
     * The public API documentation page.
     */
    public function index(): Response
    {
        return Inertia::render('Docs', [
            'api' => [
                'base_url' => url('/api/v1'),
                'filehost_url' => url('/filehost'),
                'upload_url' => url('/api/upload'),
                'max_files' => (int) config('imgz.uploads.max_files'),
                'max_size_kb' => (int) config('imgz.uploads.max_size_kb'),
                'accept' => MimeType::allowedTypes(),
                'abilities' => ApiAbility::options(),
                'throttle' => [
                    'user' => [
                        'per_minute' => (int) config('imgz.throttle.user.per_minute'),
                        'per_day' => (int) config('imgz.throttle.user.per_day'),
                    ],
                ],
            ],
        ]);
    }
}
