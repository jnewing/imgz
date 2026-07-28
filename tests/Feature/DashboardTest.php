<?php

declare(strict_types=1);

use App\Models\Image;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard lists only the user\'s own images, newest first', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $older = Image::factory()->for($user)->create(['created_at' => now()->subDay()]);
    $newer = Image::factory()->for($user)->create(['created_at' => now()]);
    Image::factory()->for($other)->create();
    Image::factory()->create(['user_id' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('images.data', 2)
            ->where('images.data.0.public_id', (string) $newer->public_id)
            ->where('images.data.1.public_id', (string) $older->public_id)
        );
});

test('the dashboard excludes expired images', function () {
    $user = User::factory()->create();

    Image::factory()->for($user)->create();
    Image::factory()->for($user)->create(['expires_at' => now()->subHour()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('images.data', 1));
});

test('the dashboard reports upload count and total size', function () {
    $user = User::factory()->create();

    Image::factory()->for($user)->create(['size' => 1000]);
    Image::factory()->for($user)->create(['size' => 2500]);
    Image::factory()->for($user)->create(['size' => 4000, 'expires_at' => now()->subHour()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.count', 2)
            ->where('stats.size', 3500)
        );
});

test('the dashboard defaults to newest-first sorting', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('filters.sort', 'newest'));
});

test('the dashboard can sort uploads oldest first', function () {
    $user = User::factory()->create();

    $older = Image::factory()->for($user)->create(['created_at' => now()->subDay()]);
    $newer = Image::factory()->for($user)->create(['created_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard', ['sort' => 'oldest']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('images.data.0.public_id', (string) $older->public_id)
            ->where('images.data.1.public_id', (string) $newer->public_id)
            ->where('filters.sort', 'oldest')
        );
});
