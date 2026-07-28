<?php

use App\Http\Middleware\AttemptSanctumAuth;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\NormalizeBearerToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // imgz runs behind a TLS-terminating reverse proxy (e.g. Pangolin /
        // Traefik) that serves HTTPS and forwards plain HTTP to the container.
        // Trust the fronting proxy's forwarded headers so generated URLs use
        // the correct scheme/host and the real client IP is seen (rate
        // limiting, logs). The container only receives proxy traffic, so we
        // trust all; to restrict, replace '*' with specific proxy IPs/CIDRs.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Runs ahead of route middleware so a raw API token (sent without the
        // "Bearer " prefix by some upload clients, e.g. Lurker's Zipline
        // uploader) is normalized before Sanctum authenticates the request.
        $middleware->prepend(NormalizeBearerToken::class);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // The FILEHOST upload endpoint is called by external IRC clients that
        // cannot supply a CSRF token; it is protected by rate limiting and the
        // upload access rules instead.
        $middleware->validateCsrfTokens(except: ['filehost']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Sanctum token-ability middleware for the API + admin gate.
        // `sanctum.optional` resolves a bearer token when present without
        // rejecting anonymous requests (used by the FILEHOST upload endpoint).
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'admin' => EnsureUserIsAdmin::class,
            'sanctum.optional' => AttemptSanctumAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
