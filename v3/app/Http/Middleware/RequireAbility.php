<?php

namespace App\Http\Middleware;

use App\Enums\TokenAbility;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Symfony\Component\HttpFoundation\Response;

class RequireAbility
{
    /**
     * @param  string  ...$abilities
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
