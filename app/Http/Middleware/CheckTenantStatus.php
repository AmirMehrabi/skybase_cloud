<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('security.enforce_active_accounts')) {
            return app(EnsureActiveAccount::class)->handle($request, $next);
        }
        $guard = auth()->guard($request->routeIs('customer.*') ? 'customer' : 'web');
        $user = $guard->user();
        if (! $user || ! $user->tenant_id) {
            return $next($request);
        }
        $tenant = $user->tenant;
        if (! $tenant || $tenant->isSuspended() || $tenant->status === 'pending') {
            $guard->logout();

            return redirect()->route($request->routeIs('customer.*') ? 'customer.login' : 'auth.login')
                ->with('error', 'Your account is unavailable. Please contact support.');
        }

        return $next($request);
    }
}
