<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRecoveryCodesHandoffCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        if (! $request->session()->get('recovery_codes_handoff_required', false)) {
            return $next($request);
        }

        if ($request->routeIs('recovery-codes.handoff') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect()->route('recovery-codes.handoff');
    }
}
