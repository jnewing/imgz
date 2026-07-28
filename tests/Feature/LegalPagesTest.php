<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

test('the terms of service page is publicly accessible', function () {
    $this->get(route('terms'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('legal/Terms'));
});

test('the acceptable use policy page is publicly accessible', function () {
    $this->get(route('aup'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('legal/AcceptableUse'));
});

test('the privacy policy page is publicly accessible', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('legal/Privacy'));
});
