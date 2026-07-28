<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedChecksum;
use App\Models\Image;
use App\Services\ImageStorer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function __construct(private readonly ImageStorer $storer) {}

    /**
     * List reported images, most-reported first.
     */
    public function index(): Response
    {
        $images = Image::query()
            ->has('reports')
            ->withCount('reports')
            ->with(['reports' => fn ($query) => $query->latest()])
            ->orderByDesc('reports_count')
            ->paginate(24);

        return Inertia::render('admin/Moderation', [
            'images' => $images->through(fn (Image $image): array => [
                'public_id' => (string) $image->public_id,
                'url' => $image->url,
                'thumbnail_url' => $image->thumbnail_url,
                'direct_url' => $image->direct_url,
                'human_size' => $image->human_size,
                'created_at' => $image->created_at?->diffForHumans(),
                'reports_count' => $image->reports_count,
                'reasons' => $image->reports->pluck('reason')
                    ->map(fn ($reason) => $reason->value)
                    ->countBy()
                    ->all(),
            ]),
        ]);
    }

    /**
     * Remove a reported image: delete it and blocklist its checksum so it
     * cannot be re-uploaded.
     */
    public function remove(Image $image): RedirectResponse
    {
        if ($image->checksum !== null) {
            BlockedChecksum::firstOrCreate(
                ['checksum' => $image->checksum],
                ['reason' => 'Removed by moderation'],
            );
        }

        $this->storer->delete($image);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Image removed and blocked.',
        ]);
    }

    /**
     * Dismiss the reports on an image, leaving it in place.
     */
    public function dismiss(Image $image): RedirectResponse
    {
        $image->reports()->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Reports dismissed.',
        ]);
    }
}
