<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enums\ApiAbility;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    /**
     * Show the API token management page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/ApiTokens', [
            'tokens' => $request->user()->tokens()->latest()->get()->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->diffForHumans(),
                'created_at' => $token->created_at->diffForHumans(),
            ])->values(),
            'abilities' => ApiAbility::options(),
            // The plaintext token, shown once immediately after creation.
            'newToken' => $request->session()->get('token'),
        ]);
    }

    /**
     * Create a new API token and flash its plaintext value once.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in(ApiAbility::values())],
        ]);

        $token = $request->user()->createToken($validated['name'], $validated['abilities']);

        return back()->with('token', $token->plainTextToken);
    }

    /**
     * Revoke an API token.
     */
    public function destroy(Request $request, string $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'API token revoked.',
        ]);
    }
}
