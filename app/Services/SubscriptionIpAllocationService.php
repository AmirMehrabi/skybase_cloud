<?php

namespace App\Services;

use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Subscription;
use App\Models\SubscriptionIpLease;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionIpAllocationService
{
    public static function isConfiguredForTenant(string $tenantId): bool
    {
        $token = config('radius_allocation.tokens.'.$tenantId);

        return is_string($token) && strlen($token) >= 32;
    }

    public function configure(Subscription $subscription, array $attributes): void
    {
        if (! $subscription->usesSessionIpAllocation() && (int) ($attributes['concurrent_devices'] ?? 1) <= 1) {
            return;
        }
        $subscription->getConnection()->transaction(function () use ($subscription, $attributes): void {
            $subscription = Subscription::query()->withTrashed()->where('tenant_id', $subscription->tenant_id)->lockForUpdate()->findOrFail($subscription->id);
            $changed = collect(['concurrent_devices', 'ip_allocation', 'ip_pool_id', 'ip_management', 'router_id', 'pppoe_username', 'reserved_ip_addresses'])
                ->contains(fn (string $key): bool => array_key_exists($key, $attributes) && $attributes[$key] != $subscription->$key);
            if (! $changed) {
                return;
            }
            foreach ($subscription->ipLeases()->whereNull('released_at')->whereNull('confirmed_at')->where('pending_until', '<', now())->get() as $expired) {
                $this->release($subscription, $expired->token, onlyExpiredPending: true);
            }
            if ($subscription->ipLeases()->whereNull('released_at')->exists()) {
                throw ValidationException::withMessages(['concurrent_devices' => 'Disconnect all active sessions before changing IP allocation or the device limit.']);
            }
            if ($subscription->connection_status === 'online') {
                throw ValidationException::withMessages(['concurrent_devices' => 'Disconnect the subscription before changing IP allocation or the device limit.']);
            }
            $previous = $subscription->reserved_ip_addresses ?? [];
            $mode = $attributes['ip_allocation'] ?? $subscription->ip_allocation ?? 'fixed';
            $addresses = $mode === 'reserved_set' ? ($attributes['reserved_ip_addresses'] ?? $previous) : [];
            $poolId = $attributes['ip_pool_id'] ?? $subscription->ip_pool_id;
            $pool = $poolId ? IpPool::query()->where('tenant_id', $subscription->tenant_id)->lockForUpdate()->findOrFail($poolId) : null;
            foreach ($addresses as $address) {
                $ip = IpAddress::query()->where('tenant_id', $subscription->tenant_id)->where('ip_pool_id', $poolId)
                    ->where('ip_address', $address)->lockForUpdate()->first();
                if (! $ip || (! $ip->isAvailable() && $ip->subscription_code !== $subscription->subscription_code)) {
                    throw ValidationException::withMessages(['reserved_ip_addresses' => 'One of the selected IP addresses is no longer available.']);
                }
                $ip->assignTo($subscription->customer, null, $subscription->subscription_code);
            }
            $released = IpAddress::query()->where('tenant_id', $subscription->tenant_id)
                ->where('subscription_code', $subscription->subscription_code)->whereIn('ip_address', $previous)
                ->where(function ($query) use ($poolId, $addresses): void {
                    $query->where('ip_pool_id', '!=', $poolId)->orWhereNotIn('ip_address', $addresses);
                })->get();
            foreach ($released as $ip) {
                $ip->release();
            }
            if ($mode !== 'fixed' && filled($subscription->ip_address)) {
                if ((string) $poolId !== (string) $subscription->ip_pool_id || ! in_array($subscription->ip_address, $addresses, true)) {
                    IpAddress::query()->where('tenant_id', $subscription->tenant_id)
                        ->where('ip_pool_id', $subscription->ip_pool_id)->where('ip_address', $subscription->ip_address)
                        ->where('subscription_code', $subscription->subscription_code)->first()?->release();
                }
                $subscription->update(['ip_address' => null]);
            }
            $subscription->update(['reserved_ip_addresses' => $addresses]);
            $pool?->updateStatistics();
            foreach ($released->pluck('ip_pool_id')->unique() as $releasedPoolId) {
                IpPool::query()->where('tenant_id', $subscription->tenant_id)->find($releasedPoolId)?->updateStatistics();
            }
        });
        $subscription->refresh();
    }

    public function allocate(Subscription $subscription, int $routerId, string $sessionKey): SubscriptionIpLease
    {
        return $subscription->getConnection()->transaction(function () use ($subscription, $routerId, $sessionKey): SubscriptionIpLease {
            $subscription = Subscription::query()->withTrashed()->where('tenant_id', $subscription->tenant_id)->lockForUpdate()->findOrFail($subscription->id);
            if ($subscription->trashed() || ! $subscription->usesSessionIpAllocation() || $subscription->status !== 'active'
                || $subscription->customer?->status !== 'active'
                || (int) $subscription->router_id !== $routerId
                || ($subscription->end_date && $subscription->end_date->isPast())) {
                throw ValidationException::withMessages(['username' => 'This subscription is not available for session IP allocation.']);
            }
            $pool = IpPool::query()->where('tenant_id', $subscription->tenant_id)->lockForUpdate()->findOrFail($subscription->ip_pool_id);
            if ($pool->status === 'disabled') {
                throw ValidationException::withMessages(['ip_pool_id' => 'The IP pool is disabled.']);
            }
            foreach ($subscription->ipLeases()->whereNull('released_at')->whereNull('confirmed_at')->where('pending_until', '<', now())->get() as $expired) {
                $this->release($subscription, $expired->token, onlyExpiredPending: true);
            }
            $key = hash('sha256', $subscription->id.':'.$sessionKey);
            $existing = $subscription->ipLeases()->where('router_id', $routerId)->where('session_key', $key)->whereNull('released_at')->first();
            if ($existing) {
                return $existing;
            }
            if ($subscription->ipLeases()->whereNull('released_at')->count() >= $subscription->concurrent_devices) {
                throw ValidationException::withMessages(['concurrent_devices' => 'Device limit reached. Disconnect a device before connecting another.']);
            }
            $ip = IpAddress::query()->where('tenant_id', $subscription->tenant_id)->where('ip_pool_id', $pool->id)
                ->when($subscription->ip_allocation === 'reserved_set',
                    fn ($query) => $query->where('subscription_code', $subscription->subscription_code)->whereIn('ip_address', $subscription->reserved_ip_addresses ?? []),
                    fn ($query) => $query->where('status', 'available'))
                ->whereNotIn('id', SubscriptionIpLease::query()->where('tenant_id', $subscription->tenant_id)->whereNotNull('ip_address_id')->select('ip_address_id'))
                ->lockForUpdate()->first();
            if (! $ip) {
                throw ValidationException::withMessages(['ip_pool_id' => 'No free IP address is available. Existing sessions remain connected.']);
            }
            if ($subscription->ip_allocation === 'dynamic_pool') {
                $ip->assignTo($subscription->customer, null, $subscription->subscription_code);
                $pool->updateStatistics();
            }

            return $subscription->ipLeases()->create([
                'tenant_id' => $subscription->tenant_id,
                'router_id' => $routerId,
                'ip_address_id' => $ip->id,
                'ip_address' => $ip->ip_address,
                'token' => (string) Str::uuid(),
                'session_key' => $key,
                'last_seen_at' => now(),
                'pending_until' => now()->addSeconds(config('radius_allocation.pending_seconds')),
            ]);
        }, 3);
    }

    public function releaseAll(Subscription $subscription): void
    {
        foreach ($subscription->ipLeases()->whereNull('released_at')->get() as $lease) {
            $this->release($subscription, $lease->token);
        }
    }

    public function releaseReservations(Subscription $subscription): void
    {
        if ($subscription->ipLeases()->whereNull('released_at')->exists()) {
            return;
        }
        $addresses = IpAddress::query()->where('tenant_id', $subscription->tenant_id)
            ->where('subscription_code', $subscription->subscription_code)
            ->whereIn('ip_address', $subscription->reserved_ip_addresses ?? [])->get();
        foreach ($addresses as $ip) {
            $ip->release();
        }
        foreach ($addresses->pluck('ip_pool_id')->unique() as $poolId) {
            IpPool::query()->where('tenant_id', $subscription->tenant_id)->find($poolId)?->updateStatistics();
        }
    }

    public function confirm(Subscription $subscription, string $token): void
    {
        $subscription->ipLeases()->where('token', $token)->whereNull('released_at')->update([
            'confirmed_at' => now(), 'last_seen_at' => now(), 'pending_until' => null,
        ]);
    }

    public function release(Subscription $subscription, string $token, bool $onlyExpiredPending = false, ?CarbonInterface $seenBefore = null): void
    {
        $subscription->getConnection()->transaction(function () use ($subscription, $token, $onlyExpiredPending, $seenBefore): void {
            $subscription = Subscription::query()->withTrashed()->where('tenant_id', $subscription->tenant_id)->lockForUpdate()->findOrFail($subscription->id);
            $pool = IpPool::query()->where('tenant_id', $subscription->tenant_id)->lockForUpdate()->find($subscription->ip_pool_id);
            $lease = $subscription->ipLeases()->where('token', $token)->whereNull('released_at')->lockForUpdate()->first();
            if (! $lease || ($onlyExpiredPending && ($lease->confirmed_at || ! $lease->pending_until?->isPast()))
                || ($seenBefore && $lease->last_seen_at->greaterThan($seenBefore))) {
                return;
            }
            $ip = IpAddress::query()->where('tenant_id', $subscription->tenant_id)->find($lease->ip_address_id);
            $lease->update(['released_at' => now(), 'session_key' => null, 'ip_address_id' => null]);
            if ($subscription->ip_allocation === 'dynamic_pool' && $ip?->subscription_code === $subscription->subscription_code) {
                $ip->release();
                $pool?->updateStatistics();
            }
            if ($subscription->trashed()) {
                $this->releaseReservations($subscription);
            }
        });
    }
}
