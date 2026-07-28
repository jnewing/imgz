<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

test('the documentation page is publicly accessible', function () {
    $this->get(route('docs'))->assertOk();
});

test('the documentation page exposes the API reference details', function () {
    $this->get(route('docs'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Docs')
            ->where('api.base_url', url('/api/v1'))
            ->where('api.filehost_url', url('/filehost'))
            ->where('api.upload_url', url('/api/upload'))
            ->where('api.max_files', (int) config('imgz.uploads.max_files'))
            ->where('api.max_size_kb', (int) config('imgz.uploads.max_size_kb'))
            ->has('api.accept')
            ->has('api.abilities', 3)
            ->where('api.throttle.user.per_minute', (int) config('imgz.throttle.user.per_minute'))
        );
});
