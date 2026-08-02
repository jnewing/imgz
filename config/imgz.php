<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Which filesystem disk uploaded images are written to. The default disk
    | ("images") is a local disk served directly by the web server (Caddy);
    | swap it for an S3-compatible disk to offload storage without touching
    | any domain code.
    |
    */

    'storage_disk' => env('IMGZ_STORAGE_DISK', 'images'),

    /*
    |--------------------------------------------------------------------------
    | Direct URL Base
    |--------------------------------------------------------------------------
    |
    | Base URL for direct image/thumbnail links. When null, direct links are
    | served same-origin at /i/{id}.{ext}. Set this to a dedicated (ideally
    | cookieless) host, e.g. https://i.imgz.net or a CDN, to serve files from
    | there instead: https://i.imgz.net/{id}.{ext}. The on-disk shard is derived
    | from the id, so it never appears in the URL and files stay statically
    | servable. No trailing slash.
    |
    */

    'direct_url' => env('IMGZ_DIRECT_URL'),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Toggles that let the same codebase run as the public imgz.net site or as
    | a locked-down self-hosted instance.
    |
    */

    'features' => [
        // Allow uploading without an account (imgbb-style anonymous uploads).
        'guest_uploads' => env('IMGZ_GUEST_UPLOADS', true),

        // Require an account to upload. When true, guest uploads are refused
        // regardless of the flag above, useful for private self-hosting.
        'require_account' => env('IMGZ_REQUIRE_ACCOUNT', false),

        // Allow new users to self-register.
        'registration' => env('IMGZ_REGISTRATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Constraints applied to incoming uploads. Sizes are in kilobytes to match
    | Laravel's validation rules.
    |
    */

    'uploads' => [
        // Maximum number of files accepted in a single upload request.
        'max_files' => (int) env('IMGZ_MAX_FILES', 20),

        // Maximum size per file, in kilobytes (default 32 MB).
        'max_size_kb' => (int) env('IMGZ_MAX_SIZE_KB', 32768),

        // MIME types accepted for upload.
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/avif',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Throttling
    |--------------------------------------------------------------------------
    |
    | Per-file upload rate limits (one request = one file). Guests are keyed by
    | IP, authenticated users by account. Both a short burst limit and a daily
    | count quota are enforced.
    |
    */

    'throttle' => [
        'guest' => [
            'per_minute' => (int) env('IMGZ_THROTTLE_GUEST_PER_MINUTE', 10),
            'per_day' => (int) env('IMGZ_THROTTLE_GUEST_PER_DAY', 100),
        ],
        'user' => [
            'per_minute' => (int) env('IMGZ_THROTTLE_USER_PER_MINUTE', 30),
            'per_day' => (int) env('IMGZ_THROTTLE_USER_PER_DAY', 1000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Variants
    |--------------------------------------------------------------------------
    |
    | Generated derivatives of an uploaded image. Thumbnails are WebP and are
    | produced asynchronously after upload; the original is always served
    | directly and untouched.
    |
    */

    'variants' => [
        // Thumbnail output format: 'webp' (default, best size) requires a
        // driver with WebP support. Set IMGZ_THUMBNAIL_FORMAT=jpg for GD builds
        // compiled without WebP (e.g. some dev machines).
        'thumbnail_format' => env('IMGZ_THUMBNAIL_FORMAT', 'webp'),
        'thumbnail_width' => (int) env('IMGZ_THUMBNAIL_WIDTH', 400),
        'thumbnail_quality' => (int) env('IMGZ_THUMBNAIL_QUALITY', 80),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Auto-expiry options offered to uploaders. `null` means "keep forever".
    | Values are in minutes and drive the expiry dropdown in the UI.
    |
    */

    'retention' => [
        'default_minutes' => env('IMGZ_DEFAULT_RETENTION_MINUTES'), // null = keep forever
        'options_minutes' => [
            5,          // 5 minutes
            60,         // 1 hour
            1440,       // 1 day
            10080,      // 1 week
            43200,      // 30 days
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | A view is only counted once per browser within this window, so refreshing
    | the page doesn't inflate the count. Set to 0 to count every request.
    |
    */

    'views' => [
        'dedupe_minutes' => (int) env('IMGZ_VIEW_DEDUPE_MINUTES', 360),
    ],

];
