<?php

namespace App\Services;

use App\Models\IpAddress;
use App\Models\IpdrBinding;
use App\Models\IpdrOutbox;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class IpdrInventoryService
{
    public function subscription(Subscription $subscription): void
    {
        if (! config('ipdr.enabled') || ! Schema::hasTable('ipdr_bindings')) {
            return;
        }
        $tenant = (string) $subscription->tenant_id;
        DB::transaction(function () use ($subscription, $tenant): void {
            $desired = [];
            if ($subscription->exists && ! $subscription->trashed() && $subscription->router_id) {
                $ipamOwned = $subscription->ip_management !== 'system' || IpAddress::query()->where('tenant_id', $tenant)->where('subscription_code', $subscription->subscription_code)->where('ip_address', $subscription->ip_address)->where('status', 'assigned')->exists();
                if ($ipamOwned && filled($subscription->ip_address) && ($subscription->ip_management === 'system' || $subscription->isStatic())) {
                    $desired['primary'] = ['address' => $subscription->ip_address, 'prefix_length' => str_contains($subscription->ip_address, ':') ? 128 : 32, 'pool_id' => $subscription->ip_pool_id, 'origin' => 'static'];
                }
                foreach ($subscription->ipRoutes()->where('tenant_id', $tenant)->get() as $route) {
                    $desired['route:'.$route->id] = ['address' => $route->ip_address, 'prefix_length' => $route->cidr, 'pool_id' => $route->ip_pool_id, 'origin' => 'route'];
                }
            }
            $keep = [];
            foreach ($desired as $key => $address) {
                if (! filter_var($address['address'], FILTER_VALIDATE_IP)) {
                    continue;
                }
                $key = 'static:'.$tenant.':'.$subscription->id.':'.$key;
                $values = $this->labels($subscription) + $address;
                $old = IpdrBinding::query()->where('tenant_id', $tenant)->where('binding_key', $key)->lockForUpdate()->first();
                if ($old && ($old->address !== $address['address'] || (int) $old->prefix_length !== (int) $address['prefix_length'] || (string) $old->router_id !== (string) $subscription->router_id)) {
                    $this->close($old);
                    $old = null;
                }
                $binding = $old ?? new IpdrBinding(['assignment_id' => (string) Str::uuid(), 'tenant_id' => $tenant, 'subscription_id' => $subscription->id, 'binding_key' => $key, 'valid_from' => now('UTC'), 'revision' => 1]);
                $this->save($binding, $values);
                $keep[] = $binding->assignment_id;
            }
            foreach (IpdrBinding::query()->where('tenant_id', $tenant)->where('subscription_id', $subscription->id)->whereIn('origin', ['static', 'route'])->whereNull('valid_until')->when($keep, fn ($q) => $q->whereNotIn('assignment_id', $keep))->lockForUpdate()->get() as $old) {
                $this->close($old);
            }
        });
    }

    private function labels(Subscription $subscription): array
    {
        return ['subscription_code' => $subscription->subscription_code, 'customer_label' => $subscription->name ?: $subscription->subscription_code, 'router_id' => (string) $subscription->router_id];
    }

    private function close(IpdrBinding $binding): void
    {
        $this->save($binding, ['valid_until' => now('UTC'), 'binding_key' => null]);
    }

    private function save(IpdrBinding $binding, array $values): void
    {
        $binding->fill($values);
        if (! $binding->exists || $binding->isDirty()) {
            if ($binding->exists) {
                $binding->revision++;
            }
            $binding->save();
            IpdrOutbox::query()->create(['tenant_id' => $binding->tenant_id, 'payload' => $binding->integrationPayload()]);
        }
    }

    public function radius(string $tenantId): void
    {
        if (! Schema::hasTable('radacct')) {
            return;
        }
        $timezone = config('ipdr.radius_timezone');
        foreach (Router::query()->where('tenant_id', $tenantId)->get() as $router) {
            // Legacy accounting has no tenant ID: require a globally unambiguous NAS owner.
            $nasOwners = 0;
            foreach (Tenant::query()->cursor() as $candidateTenant) {
                if (Router::withoutGlobalScopes()->where('tenant_id', $candidateTenant->id)->where('ip_address', $router->ip_address)->exists()) {
                    $nasOwners++;
                }
            }
            if ($nasOwners !== 1) {
                continue;
            }
            $subscriptions = Subscription::query()->where('tenant_id', $tenantId)->where('router_id', $router->id)->whereNotNull('pppoe_username')->get()->groupBy('pppoe_username');
            $sessions = DB::table('radacct')->where('nasipaddress', $router->ip_address)->whereIn('username', $subscriptions->keys())
                ->whereNotNull('acctstarttime')->where(fn ($q) => $q->whereNull('acctstoptime')->orWhere('acctstoptime', '>=', now()->setTimezone($timezone)->subDays(config('ipdr.radius_history_days'))->toDateTimeString()))->orderBy('radacctid')->cursor();
            foreach ($sessions as $session) {
                $owners = $subscriptions->get($session->username);
                if ($owners->count() !== 1) {
                    continue;
                }
                $subscription = $owners->first();
                $start = CarbonImmutable::parse($session->acctstarttime, $timezone)->utc();
                if ($subscription->created_at && $start < $subscription->created_at->toImmutable()->utc()) {
                    continue;
                }
                $last = CarbonImmutable::parse($session->acctupdatetime ?: $session->acctstarttime, $timezone)->utc();
                $end = $session->acctstoptime ? CarbonImmutable::parse($session->acctstoptime, $timezone)->utc() : $last->addMinutes(config('ipdr.radius_stale_minutes'));
                // Bound open sessions to the last observed heartbeat plus a configured grace interval.
                foreach (['framedipaddress', 'framedipv6address', 'framedipv6prefix', 'delegatedipv6prefix'] as $field) {
                    $raw = $session->{$field} ?? null;
                    if (blank($raw)) {
                        continue;
                    }
                    $parts = explode('/', $raw);
                    $ip = $parts[0];
                    if (! filter_var($ip, FILTER_VALIDATE_IP) || in_array($ip, ['0.0.0.0', '::'], true)) {
                        continue;
                    }
                    DB::transaction(function () use ($tenantId, $session, $subscription, $start, $end, $ip, $parts, $field): void {
                        $key = 'radius:'.$tenantId.':'.$session->radacctid.':'.$field;
                        $binding = IpdrBinding::query()->where('tenant_id', $tenantId)->where('binding_key', $key)->lockForUpdate()->first();
                        if ($binding && (int) $binding->subscription_id !== (int) $subscription->id) {
                            return;
                        }
                        $binding ??= new IpdrBinding(['assignment_id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'subscription_id' => $subscription->id, 'binding_key' => $key, 'revision' => 1]);
                        $this->save($binding, $this->labels($subscription) + ['address' => $ip, 'prefix_length' => isset($parts[1]) ? (int) $parts[1] : (str_contains($ip, ':') ? 128 : 32), 'origin' => 'radius', 'valid_from' => $start, 'valid_until' => $end->max($start), 'pool_id' => $subscription->ip_pool_id === null ? null : (string) $subscription->ip_pool_id]);
                    });
                }
            }
        }
    }

    public function deliver(string $tenantId, IpdrClient $client): int
    {
        $routers = Router::query()->where('tenant_id', $tenantId)->get()->map(fn ($router) => ['router_id' => (string) $router->id, 'name' => $router->name, 'exporter_address' => $router->ip_address, 'enabled' => (bool) $router->netflow_enabled])->all();
        $count = 0;
        $client->inventory($tenantId, $routers, []);
        IpdrOutbox::query()->where('tenant_id', $tenantId)->whereNull('delivered_at')->orderBy('id')->chunkById(250, function ($messages) use ($client, $tenantId, $routers, &$count): void {
            $client->inventory($tenantId, $routers, $messages->pluck('payload')->all());
            IpdrOutbox::query()->where('tenant_id', $tenantId)->whereIn('id', $messages->modelKeys())->update(['delivered_at' => now()]);
            $count += $messages->count();
        });

        return $count;
    }
}
