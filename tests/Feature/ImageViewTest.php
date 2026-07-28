<?php

declare(strict_types=1);

use App\Models\Image;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the view page for an image', function () {
    $image = Image::factory()->create();

    $this->get("/i/{$image->public_id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('image/Show')
            ->where('image.public_id', (string) $image->public_id)
        );
});

it('increments the view count', function () {
    $image = Image::factory()->create(['views' => 4]);

    $this->get("/i/{$image->public_id}");

    expect($image->fresh()->views)->toBe(5);
});

it('does not leak the delete token on the view page', function () {
    $image = Image::factory()->create();

    $this->get("/i/{$image->public_id}")
        ->assertInertia(fn (Assert $page) => $page->missing('image.delete_url'));
});

it('returns 404 for an expired image', function () {
    $image = Image::factory()->expired()->create();

    $this->get("/i/{$image->public_id}")->assertNotFound();
});

it('shows any non-expired image to anyone with the link', function () {
    $anonymous = Image::factory()->anonymous()->create();
    $owned = Image::factory()->for(User::factory())->create();

    $this->get("/i/{$anonymous->public_id}")->assertOk();
    $this->get("/i/{$owned->public_id}")->assertOk();
});

it('shows the delete confirmation page for a valid token', function () {
    $image = Image::factory()->create();

    $this->get("/i/{$image->public_id}/delete/{$image->delete_token}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('image/ConfirmDelete'));
});

it('returns 404 for a delete confirmation with the wrong token', function () {
    $image = Image::factory()->create();

    $this->get("/i/{$image->public_id}/delete/wrong-token")->assertNotFound();
});
