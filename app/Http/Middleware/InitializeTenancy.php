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
        if ($user?->isCentralAdmin() && $request->routeIs('dashboard') && config('skybase_billing.owner_console_enabled')) {
            return redirect()->route('admin.super-admin.tenants.index');
        }
        abort_unless($user && filled($user->tenant_id), 403);
        $tenant = Tenant::query()->find($user->tenant_id);
        abort_unless($tenant && (string) $user->tenant_id === (string) $tenant->id, 403);

        abort_if($tenant->application_access_suspended || $tenant->archived_at, 403, 'Application access is suspended.');

        return app(TenantContext::class)->run((string) $tenant->id, fn (): Response => $next($request));
    }
}
