<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancy
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

        $tenant = Tenant::find($user->tenant_id);

        if (! $tenant) {
            auth()->logout();

            return redirect()->route('login')->with('error', 'Tenant not found.');
        }

        tenancy()->initialize($tenant);

        app()->instance('current_tenant', $tenant);

        return $next($request);
    }
}
