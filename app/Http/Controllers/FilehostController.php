<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\BlockedImageException;
use App\Exceptions\FileStorageException;
use App\Exceptions\UnsupportedMimeTypeException;
use App\Services\ImageStorer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * IRCv3 draft/FILEHOST upload endpoint. POST the raw file bytes (or a multipart
 * file/image field) and get back a 201 with the hosted URL in both the Location
 * header and the body. Anonymous when the instance allows guests; a bearer token
 * attributes the upload to its owner.
 */
class FilehostController extends Controller
{
    public function __construct(private readonly ImageStorer $storer) {}

    public function store(Request $request): Response
    {
        if (($denied = $this->guardAccess($request)) !== null) {
            return $denied;
        }

        $file = $this->resolveUpload($request);

        if ($file === null) {
            return $this->plain('No image data was received.', HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $maxBytes = (int) config('imgz.uploads.max_size_kb') * 1024;

        if (($file->getSize() ?: 0) > $maxBytes) {
            return $this->plain(
                'The image exceeds the maximum size of '.round($maxBytes / 1048576).' MB.',
                HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $image = $this->storer->store($file, $request->user());
        } catch (UnsupportedMimeTypeException $e) {
            return $this->plain($e->getUserMessage(), HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (BlockedImageException) {
            return $this->plain('This image has been blocked and cannot be uploaded.', HttpResponse::HTTP_FORBIDDEN);
        } catch (FileStorageException) {
            return $this->plain('The image could not be stored. Please try again.', HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $url = $image->direct_url;

        return response($url, HttpResponse::HTTP_CREATED, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Location' => $url,
        ]);
    }

    public function options(): Response
    {
        /** @var string[] $mimes */
        $mimes = config('imgz.uploads.allowed_mimes', []);

        return response('', HttpResponse::HTTP_NO_CONTENT, [
            'Accept-Post' => implode(', ', $mimes),
            'Allow' => 'OPTIONS, POST',
        ]);
    }

    /**
     * Same access rules as the web upload. Returns an error response when the
     * request isn't allowed, or null when it is.
     */
    private function guardAccess(Request $request): ?Response
    {
        $user = $request->user();

        if ($user !== null) {
            // authenticated callers need the write ability on their token
            if (! $user->tokenCan('images:write')) {
                return $this->plain('This API token is not permitted to upload images.', HttpResponse::HTTP_FORBIDDEN);
            }

            return null;
        }

        $guestAllowed = (bool) config('imgz.features.guest_uploads')
            && ! config('imgz.features.require_account');

        if (! $guestAllowed) {
            return $this->plain(
                'This instance requires an account to upload. Provide an API token via the Authorization header.',
                HttpResponse::HTTP_UNAUTHORIZED,
            );
        }

        return null;
    }

    private function resolveUpload(Request $request): ?UploadedFile
    {
        foreach (['file', 'image'] as $field) {
            if ($request->hasFile($field)) {
                /** @var UploadedFile $file */
                $file = $request->file($field);

                return $file;
            }
        }

        $content = $request->getContent();

        if ($content === '') {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'imgz');
        file_put_contents($path, $content);

        // last arg marks it a test file so it skips the is_uploaded_file() check
        return new UploadedFile($path, $this->clientFilename($request), null, null, true);
    }

    /**
     * Pull a filename hint out of the Content-Disposition header, if there is one.
     */
    private function clientFilename(Request $request): string
    {
        $disposition = $request->header('Content-Disposition');

        if (is_string($disposition) && preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $disposition, $matches) === 1) {
            $name = basename(trim(rawurldecode($matches[1])));

            if ($name !== '') {
                return $name;
            }
        }

        return 'upload';
    }

    /**
     * Plain-text response so IRC clients show the message as-is.
     */
    private function plain(string $message, int $status): Response
    {
        return response($message, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
