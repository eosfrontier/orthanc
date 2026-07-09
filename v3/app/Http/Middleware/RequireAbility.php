<?php

namespace App\Http\Middleware;

use App\Enums\TokenAbility;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that authorizes requests based on Sanctum token abilities.
 *
 * Uses ANY semantics: the request passes if the token holds at least one
 * of the listed abilities. Register as route middleware via the
 * 'require-ability' alias:
 *
 *   Route::middleware(['auth:sanctum', 'require-ability:storage:read'])
 *
 * Throws:
 * - 500 (ValueError) if an invalid ability string is passed (config error)
 * - 401 (AuthenticationException) if no authenticated user/token
 * - 403 (MissingAbilityException) if the token lacks all listed abilities
 */
class RequireAbility
{
    /**
     * Handle the incoming request.
     *
     * @param  string  ...$abilities  One or more TokenAbility values to check against
     *
     * @throws \ValueError                If an ability string is not a valid TokenAbility case
     * @throws AuthenticationException    If no user or token is present
     * @throws MissingAbilityException    If the token lacks all listed abilities
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        // Validate ability strings against the enum (catches config typos)
        foreach ($abilities as $ability) {
            TokenAbility::from($ability);
        }

        if (! $request->user() || ! $request->user()->currentAccessToken()) {
            throw new AuthenticationException();
        }

        foreach ($abilities as $ability) {
            if ($request->user()->tokenCan($ability)) {
                return $next($request);
            }
        }

        throw new MissingAbilityException($abilities);
    }
}
