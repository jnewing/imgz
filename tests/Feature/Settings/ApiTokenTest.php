<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('requires authentication to manage tokens', function () {
    $this->get('/settings/api-tokens')->assertRedirect('/login');
});

it('renders the token page with abilities', function () {
    $this->actingAs(User::factory()->create())
        ->get('/settings/api-tokens')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/ApiTokens')
            ->has('abilities')
            ->has('tokens')
        );
});

it('creates a token and flashes the plaintext once', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/settings/api-tokens', [
            'name' => 'My script',
            'abilities' => ['images:write', 'images:read'],
        ])
        ->assertSessionHas('token');

    $token = $user->tokens()->firstOrFail();
    expect($token->name)->toBe('My script')
        ->and($token->abilities)->toContain('images:write');
});

it('validates the requested abilities', function () {
    $this->actingAs(User::factory()->create())
        ->post('/settings/api-tokens', [
            'name' => 'Bad',
            'abilities' => ['images:everything'],
        ])
        ->assertSessionHasErrors('abilities.0');
});

it('requires at least one ability', function () {
    $this->actingAs(User::factory()->create())
        ->post('/settings/api-tokens', ['name' => 'None', 'abilities' => []])
        ->assertSessionHasErrors('abilities');
});

it('revokes a token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('temp', ['images:read']);
    $id = $token->accessToken->getKey();

    $this->actingAs($user)
        ->delete("/settings/api-tokens/{$id}")
        ->assertRedirect();

    expect($user->tokens()->count())->toBe(0);
});
