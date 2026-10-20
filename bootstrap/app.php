<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckTenantStatus;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureGolestanEnabled;
use App\Http\Middleware\EnsureMultiFactorAuthentication;
use App\Http\Middleware\InitializeTenancy;
use App\Http\Middleware\ScanUploads;
use App\Http\Middleware\SecurityHeaders;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToPriorityList(AuthenticatesRequests::class, EnsureGolestanEnabled::class);
        $middleware->web(append: [ScanUploads::class]);
        $middleware->append(SecurityHeaders::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, InitializeTenancy::class);
        $middleware->alias([
            'initialize_tenancy' => InitializeTenancy::class,
            'check_tenant_status' => CheckTenantStatus::class,
            'permission' => CheckPermission::class,
            'active_account' => EnsureActiveAccount::class,
            'mfa' => EnsureMultiFactorAuthentication::class,
        ]);

        $middleware->redirectGuestsTo(function ($request): string {
            if ($request->routeIs('customer.*')) {
                return route('customer.login');
            }

            return '/auth/login';
        });

        $middleware->redirectUsersTo(function ($request): string {
            $landingRoute = auth()->check()
                ? PermissionRegistry::firstAccessibleRoute(auth()->user()) ?? 'dashboard'
                : 'dashboard';

            if ($request->routeIs('customer.*')) {
                return route('customer.dashboard');
            }

            return route($landingRoute);
        });

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation', 'pppoe_password', 'api_password', 'coa_secret', 'incoming_password', 'outgoing_password', 'code', 'recovery_code', 'twilio.token', 'smsportal.secret', 'kavenegar.api_key']);
    })->create();
