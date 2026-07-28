<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

it('renders the landing page with upload limits and retention options', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('limits.guest_uploads', true)
            ->has('limits.accept')
            ->has('limits.max_size_kb')
            ->has('retentionOptions.0.value')
            ->has('retentionOptions.0.label')
        );
});

it('reflects account-required mode in the limits', function () {
    config(['imgz.features.require_account' => true]);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('limits.guest_uploads', false));
});
