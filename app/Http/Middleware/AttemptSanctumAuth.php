<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve a Sanctum bearer token if one is present, without rejecting the request
 * when it isn't. Lets anonymous-friendly endpoints (like the FILEHOST upload) still
 * attribute to a token's owner and get the user-tier rate limit. No token = guest.
 */
class AttemptSanctumAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('sanctum')->user();

        if ($user !== null) {
            Auth::setUser($user);
        }

        return $next($request);
    }
}
