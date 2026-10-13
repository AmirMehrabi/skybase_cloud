<?php

namespace App\Http\Middleware;

use App\Services\SecurityEventLogger;
use App\Support\Rbac\PermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        if (! auth()->check()) {
            return redirect()->route('auth.login');
        }

        $user = auth()->user();
        $permission ??= $this->permissionForRequest($request);

        if ($permission === null) {
            abort_unless(! config('security.strict_route_permissions') || $request->routeIs('profile.*'), 403, PermissionRegistry::DENIED_MESSAGE);

            return $next($request);
        }

        if ($user->isCentralAdmin()) {
            return $next($request);
        }

        if (! $user->hasPermission($permission)) {
            app(SecurityEventLogger::class)->record('authorization.denied', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id, 'route' => $request->route()?->getName(), 'ip' => $request->ip()]);
            if ($request->routeIs('dashboard')) {
                $landingRoute = PermissionRegistry::firstAccessibleRoute($user);

                if ($landingRoute) {
                    return redirect()->route($landingRoute);
                }
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => PermissionRegistry::DENIED_MESSAGE], 403);
            }

            abort(403, PermissionRegistry::DENIED_MESSAGE);
        }

        return $next($request);
    }

    private function permissionForRequest(Request $request): ?string
    {
        if ($request->routeIs('import-export.*')) {
            return match ($request->route('module')) {
                'plans' => 'plans.read',
                'subscriptions' => 'subscriptions.read',
                default => null,
            };
        }

        return PermissionRegistry::routePermission($request->route()?->getName());
    }
}
