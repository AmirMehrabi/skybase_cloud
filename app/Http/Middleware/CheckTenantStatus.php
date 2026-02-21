<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if (! $user->tenant_id) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            auth()->logout();

            return redirect()->route('login')->with('error', 'Tenant not found.');
        }

        if ($tenant->isSuspended()) {
            auth()->logout();

            return redirect()->route('login')->with('error', 'Your account has been suspended. Please contact support.');
        }

        if ($tenant->status === 'pending') {
            return redirect()->route('pending.activation');
        }

        return $next($request);
    }
}
