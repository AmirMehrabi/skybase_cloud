<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = auth()->check() ? auth() : auth('customer');

        if (! $guard->check()) {
            return $next($request);
        }

        $user = $guard->user();

        if (! $user->tenant_id) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            $guard->logout();

            return redirect()->route($request->routeIs('customer.*') ? 'customer.login' : 'auth.login')->with('error', 'Tenant not found.');
        }

        if ($tenant->isSuspended()) {
            $guard->logout();

            return redirect()->route($request->routeIs('customer.*') ? 'customer.login' : 'auth.login')->with('error', 'Your account has been suspended. Please contact support.');
        }

        if ($tenant->status === 'pending') {
            if ($request->routeIs('customer.*')) {
                $guard->logout();

                return redirect()->route('customer.login')->with('error', 'Your account is pending activation. Please contact support.');
            }

            return redirect()->route('pending.activation');
        }

        return $next($request);
    }
}
