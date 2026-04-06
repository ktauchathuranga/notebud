<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastUsedAt
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if ($user === null) {
            return $response;
        }

        $now = now();
        $shouldRefreshLastUsedAt = $user->last_used_at === null
            || $user->last_used_at->lt($now->copy()->subMinute());

        if ($shouldRefreshLastUsedAt) {
            $user->forceFill([
                'last_used_at' => $now,
            ])->saveQuietly();
        }

        return $response;
    }
}
