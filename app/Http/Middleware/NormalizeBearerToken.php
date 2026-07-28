<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turn a non-bearer Authorization header into a bearer token so Sanctum can read
 * it. Handles a bare "{id}|{hash}" token (Lurker's Zipline uploader sends it that
 * way) and HTTP Basic, where a FILEHOST client puts the token in the password or
 * username of a SASL PLAIN login (e.g. Halloy's filehost.credentials.plain).
 *
 * Runs globally, before auth. Only rewrites the header when the value is exactly a
 * Sanctum token, so anything else falls through untouched.
 */
class NormalizeBearerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (is_string($header)) {
            $token = $this->extractToken($header);

            if ($token !== null) {
                $request->headers->set('Authorization', 'Bearer '.$token);
            }
        }

        return $next($request);
    }

    /**
     * Pull a Sanctum plaintext token out of a bare-token or HTTP Basic header.
     */
    private function extractToken(string $header): ?string
    {
        $header = trim($header);

        if ($this->looksLikeToken($header)) {
            return $header;
        }

        if (preg_match('/^Basic\s+(\S+)$/i', $header, $matches) === 1) {
            $decoded = base64_decode($matches[1], true);

            if ($decoded !== false && str_contains($decoded, ':')) {
                [$username, $password] = explode(':', $decoded, 2);

                foreach ([$password, $username] as $candidate) {
                    if ($this->looksLikeToken($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    private function looksLikeToken(string $value): bool
    {
        return preg_match('/^\d+\|[A-Za-z0-9]+$/', $value) === 1;
    }
}
