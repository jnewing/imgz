<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReportReason;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Services\ImageStorer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ImageController extends Controller
{
    public function __construct(private readonly ImageStorer $storer) {}

    /**
     * Display an image's view page.
     */
    public function show(Request $request, Image $image): Response
    {
        // Images are unlisted and reachable by anyone with the link; only
        // expired uploads are gone.
        abort_if($image->isExpired(), HttpResponse::HTTP_NOT_FOUND);

        $this->countView($request, $image);

        return Inertia::render('image/Show', [
            'image' => (new ImageResource($image))->resolve($request),
            'reportReasons' => ReportReason::options(),
        ]);
    }

    /**
     * Count a view, but only once per browser within the dedupe window, so
     * refreshing the page doesn't inflate the count. Keyed by a per-browser
     * cookie rather than IP, so viewers behind the same NAT count separately.
     */
    private function countView(Request $request, Image $image): void
    {
        $minutes = (int) config('imgz.views.dedupe_minutes');

        if ($minutes <= 0) {
            $image->increment('views');

            return;
        }

        // first request has no cookie yet, so mint one and use it right away;
        // the browser gets it on this response and sends it back next time.
        $visitor = $request->cookie('imgz_visitor');

        if (! is_string($visitor) || $visitor === '') {
            $visitor = Str::random(32);
            Cookie::queue('imgz_visitor', $visitor, 60 * 24 * 365);
        }

        $key = "image-view:{$image->public_id}:{$visitor}";

        // Cache::add is atomic and only succeeds when the key is absent, so the
        // first hit in the window counts and later refreshes don't.
        if (Cache::add($key, true, now()->addMinutes($minutes))) {
            $image->increment('views');
        }
    }

    /**
     * Show the guest deletion confirmation page (reached via the delete_url
     * capability link handed to the uploader).
     */
    public function confirmDelete(Image $image, string $token): Response
    {
        abort_unless(hash_equals((string) $image->delete_token, $token), HttpResponse::HTTP_NOT_FOUND);

        return Inertia::render('image/ConfirmDelete', [
            'image' => (new ImageResource($image))->resolve(),
            'token' => $token,
        ]);
    }

    /**
     * Delete an image. Permitted for the owner, or anyone presenting the
     * matching delete token (the guest deletion capability). Responds with an
     * Inertia redirect for page-driven deletes and JSON for XHR callers.
     */
    public function destroy(Request $request, Image $image): JsonResponse|RedirectResponse
    {
        $viaToken = $this->deleteTokenMatches($request, $image);

        abort_unless($viaToken || $this->ownsImage($image), HttpResponse::HTTP_FORBIDDEN);

        $this->storer->delete($image);

        if ($request->header('X-Inertia')) {
            // Guests deleting via the capability link have nowhere to return
            // to, so send them home; owners deleting from their dashboard stay
            // put.
            $redirect = $viaToken ? redirect()->route('home') : back();

            return $redirect->with('toast', [
                'type' => 'success',
                'message' => 'Image deleted.',
            ]);
        }

        return response()->json(['deleted' => true]);
    }

    /**
     * Whether the authenticated user owns the image.
     */
    private function ownsImage(Image $image): bool
    {
        return $image->user_id !== null && $image->user_id === Auth::id();
    }

    /**
     * Whether the request carries a valid guest deletion token for the image.
     */
    private function deleteTokenMatches(Request $request, Image $image): bool
    {
        $token = (string) $request->input('token', '');

        return $token !== '' && hash_equals((string) $image->delete_token, $token);
    }
}
