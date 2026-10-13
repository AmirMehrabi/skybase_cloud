<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->routeIs('customer.*') ? 'customer' : 'web';
        $guard = Auth::guard($name);
        $user = $guard->user();
        if (config('security.enforce_active_accounts') && $user !== null && ($user->status !== 'active' || ($user->tenant_id && $user->tenant?->status !== 'active'))) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Account access is disabled.');
        }

        return $next($request);
    }
}
