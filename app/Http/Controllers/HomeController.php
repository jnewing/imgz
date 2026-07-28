<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ValueObjects\MimeType;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * The landing page: the upload experience.
     */
    public function index(): Response
    {
        return Inertia::render('Home', [
            'limits' => [
                'max_files' => (int) config('imgz.uploads.max_files'),
                'max_size_kb' => (int) config('imgz.uploads.max_size_kb'),
                'accept' => MimeType::allowedTypes(),
                'guest_uploads' => (bool) config('imgz.features.guest_uploads')
                    && ! config('imgz.features.require_account'),
                'is_authenticated' => Auth::check(),
            ],
            'retentionOptions' => $this->retentionOptions(),
        ]);
    }

    /**
     * The retention choices offered in the expiry selector, newest-lived last.
     *
     * @return list<array{value: int, label: string}>
     */
    private function retentionOptions(): array
    {
        /** @var int[] $minutes */
        $minutes = config('imgz.retention.options_minutes', []);

        return array_values(array_map(fn (int $value): array => [
            'value' => $value,
            'label' => $this->humanMinutes($value),
        ], $minutes));
    }

    private function humanMinutes(int $minutes): string
    {
        return match (true) {
            $minutes < 60 => "{$minutes} minutes",
            $minutes < 1440 => $this->plural($minutes / 60, 'hour'),
            $minutes < 10080 => $this->plural($minutes / 1440, 'day'),
            $minutes < 43200 => $this->plural($minutes / 10080, 'week'),
            default => $this->plural($minutes / 43200, 'month'),
        };
    }

    private function plural(float $value, string $unit): string
    {
        $count = (int) round($value);

        return $count.' '.$unit.($count === 1 ? '' : 's');
    }
}
