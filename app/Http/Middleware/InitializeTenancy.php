<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancy
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard($request->routeIs('customer.*') ? 'customer' : 'web');
        $user = $guard->user();
        abort_unless($user && filled($user->tenant_id), 403);
        $tenant = Tenant::query()->find($user->tenant_id);
        abort_unless($tenant && (string) $user->tenant_id === (string) $tenant->id, 403);

        return app(TenantContext::class)->run((string) $tenant->id, fn (): Response => $next($request));
    }
}
