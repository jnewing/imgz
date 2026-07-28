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

        $image->increment('views');

        return Inertia::render('image/Show', [
            'image' => (new ImageResource($image))->resolve($request),
            'reportReasons' => ReportReason::options(),
        ]);
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
