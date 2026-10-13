<?php

namespace App\Providers;

use App\Models\User;
use App\Services\CentralIdentityProvider;
use App\Services\SecurityEventLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('central-identity', fn ($app, array $config): CentralIdentityProvider => new CentralIdentityProvider($app['hash'], $config['model']));
        Gate::define('manage-central-tenants', fn (User $user): bool => $user->isCentralAdmin());
        RateLimiter::for('login', fn (Request $request): array => config('security.enforce_rate_limits') ? [
            Limit::perMinute(20)->by('login-ip:'.$request->ip()),
            Limit::perMinute(5)->by('login-account:'.hash('sha256', strtolower((string) $request->input('tenant')).'|'.strtolower((string) $request->input('email')))),
        ] : [Limit::none()]);
        RateLimiter::for('password-changes', fn (Request $request): Limit => config('security.enforce_rate_limits') ? Limit::perMinute(6)->by((string) $request->user()?->id.'|'.$request->ip()) : Limit::none());
        RateLimiter::for('public-forms', fn (Request $request): Limit => config('security.enforce_rate_limits') ? Limit::perMinute(5)->by($request->ip()) : Limit::none());
        RateLimiter::for('sensitive-actions', fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->user()?->id.'|'.$request->ip()));
        foreach ([Login::class, Failed::class, Logout::class] as $event) {
            Event::listen($event, function (Login|Failed|Logout $event): void {
                app(SecurityEventLogger::class)->record('authentication.'.class_basename($event), [
                    'user_id' => $event->user?->getAuthIdentifier(),
                    'tenant_id' => $event->user?->tenant_id,
                    'guard' => $event->guard,
                    'ip' => request()->ip(),
                ]);
            });
        }
    }
}
