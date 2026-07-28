<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ImageResource;
use App\Models\Image;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The signed-in user's upload history, as an infinite-scrolling masonry
     * grid. Supports toggling the sort order.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $sort = $request->string('sort')->toString() === 'oldest' ? 'oldest' : 'newest';

        $base = Image::query()
            ->forUser($user->id)
            ->notExpired();

        $images = (clone $base)
            ->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Dashboard', [
            'images' => Inertia::scroll(fn () => ImageResource::collection($images)),
            'stats' => [
                'count' => (clone $base)->count(),
                'size' => (int) (clone $base)->sum('size'),
            ],
            'filters' => [
                'sort' => $sort,
            ],
        ]);
    }
}
