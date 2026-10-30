<?php

namespace App\Providers;

use App\Models\IpAddress;
use App\Models\Subscription;
use App\Models\SubscriptionIpRoute;
use App\Models\User;
use App\Services\CentralIdentityProvider;
use App\Services\IpdrInventoryService;
use App\Services\SecurityEventLogger;
use App\Services\TenantNotificationService;
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
        Event::listen('eloquent.updated: '.Subscription::class, function (Subscription $subscription): void {
            $event = $subscription->wasChanged('status') && $subscription->status === 'cancelled' ? 'subscription.cancelled' : ($subscription->wasChanged('plan_id') ? 'subscription.plan_changed' : null);
            if ($event) {
                $subscription->loadMissing('customer');
                $payload = ['title' => $event === 'subscription.cancelled' ? 'Subscription cancelled' : 'Subscription service changed', 'body' => $subscription->subscription_code, 'subscription_id' => $subscription->id, 'event_identity' => $subscription->id.':'.$event.':'.$subscription->updated_at->format('U.u')];
                $notifications = app(TenantNotificationService::class);
                if ($subscription->customer) {
                    $notifications->notifyCustomer($subscription->customer, $event, $payload, $subscription);
                }
                $notifications->notifyAdmins($subscription->tenant_id, $event, $payload, $subscription);
            }
        });
        foreach (['saved', 'deleted', 'restored'] as $event) {
            Event::listen('eloquent.'.$event.': '.Subscription::class, function (Subscription $subscription): void {
                app(IpdrInventoryService::class)->subscription($subscription);
            });
            Event::listen('eloquent.'.$event.': '.SubscriptionIpRoute::class, function (SubscriptionIpRoute $route): void {
                if (! config('ipdr.enabled')) {
                    return;
                }
                $subscription = Subscription::query()->where('tenant_id', $route->tenant_id)->withTrashed()->find($route->subscription_id);
                if ($subscription) {
                    app(IpdrInventoryService::class)->subscription($subscription);
                }
            });
            Event::listen('eloquent.'.$event.': '.IpAddress::class, function (IpAddress $address): void {
                if (! config('ipdr.enabled')) {
                    return;
                }
                $codes = array_filter([$address->subscription_code, $address->getOriginal('subscription_code')]);
                foreach (Subscription::query()->where('tenant_id', $address->tenant_id)->whereIn('subscription_code', $codes)->get() as $subscription) {
                    app(IpdrInventoryService::class)->subscription($subscription);
                }
            });
        }
        Auth::provider('central-identity', fn ($app, array $config): CentralIdentityProvider => new CentralIdentityProvider($app['hash'], $config['model']));
        Gate::define('manage-central-tenants', fn (User $user): bool => $user->isCentralAdmin());
        RateLimiter::for('login', fn (Request $request): array => config('security.enforce_rate_limits') ? [
            Limit::perMinute(20)->by('login-ip:'.$request->ip()),
            Limit::perMinute(5)->by('login-account:'.hash('sha256', strtolower((string) $request->input('tenant')).'|'.strtolower((string) $request->input('email')))),
        ] : [Limit::none()]);
        RateLimiter::for('password-changes', fn (Request $request): Limit => config('security.enforce_rate_limits') ? Limit::perMinute(6)->by((string) $request->user()?->id.'|'.$request->ip()) : Limit::none());
        RateLimiter::for('contact-inquiries', fn (Request $request): array => [
            Limit::perMinute(3)->by('contact-ip:'.$request->ip()),
            Limit::perHour(5)->by('contact-email:'.hash('sha256', strtolower((string) $request->input('email')))),
        ]);
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
