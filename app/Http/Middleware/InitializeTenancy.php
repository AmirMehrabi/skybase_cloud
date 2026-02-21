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

        // Verify the user belongs to the tenant (security check)
        if ($user->tenant_id !== $tenant->id) {
            auth()->logout();

            return redirect()->route('login')->with('error', 'Invalid tenant access.');
        }

        // Store the tenant in the app container for global access
        app()->instance('current_tenant', $tenant);

        // Set a global tenant_id in the request for easy access
        $request->merge(['_tenant_id' => $tenant->id]);

        return $next($request);
    }
}
