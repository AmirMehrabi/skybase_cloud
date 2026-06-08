<?php

namespace App\Services;

use App\Models\RadiusReply;
use App\Models\Subscription;
use App\Models\SubscriptionIpRoute;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SubscriptionIpRouteSyncService
{
    public function syncRoutes(Subscription $subscription): void
    {
        $subscription->loadMissing(['customer.organization', 'plan', 'ipRoutes']);

        Log::info('Subscription IP route RADIUS sync started.', [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'route_count' => $subscription->ipRoutes->count(),
        ]);

        $skipReason = $this->skipReason($subscription);
        if ($skipReason !== null) {
            $this->deleteFramedRoutes($subscription);

            foreach ($subscription->ipRoutes as $route) {
                $this->markSkipped($route, $skipReason);
            }

            Log::info('Subscription IP route RADIUS sync skipped.', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'route_count' => $subscription->ipRoutes->count(),
                'reason' => $skipReason,
            ]);

            return;
        }

        $this->ensureFramedRouteRepliesAllowMultipleRows();

        DB::transaction(function () use ($subscription): void {
            $tenantId = (string) $subscription->tenant_id;
            $username = (string) $subscription->pppoe_username;

            $this->syncFramedIpAddress($subscription);
            $this->deleteFramedRoutes($subscription);

            foreach ($subscription->ipRoutes as $route) {
                RadiusReply::withoutGlobalScopes()->create([
                    'tenant_id' => $tenantId,
                    'username' => $username,
                    'attribute' => 'Framed-Route',
                    'op' => '+=',
                    'value' => $this->framedRouteValue($route, $subscription),
                ]);

                $route->forceFill([
                    'routeros_sync_status' => 'synced',
                    'routeros_sync_error' => null,
                    'routeros_synced_at' => now(),
                ])->save();
            }
        });

        Log::info('Subscription IP route RADIUS sync completed.', [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'route_count' => $subscription->ipRoutes->count(),
        ]);
    }

    public function removeRoutes(Subscription $subscription): void
    {
        $subscription->loadMissing('ipRoutes');

        $this->deleteFramedRoutes($subscription);
    }

    public function syncRoute(SubscriptionIpRoute $route, ?Subscription $subscription = null): void
    {
        $subscription ??= $route->subscription;

        if ($subscription) {
            $this->syncRoutes($subscription);
        }
    }

    public function removeRoute(SubscriptionIpRoute $route, ?Subscription $subscription = null): void
    {
        $subscription ??= $route->subscription;

        if (! $subscription || blank($subscription->tenant_id) || blank($subscription->pppoe_username)) {
            return;
        }

        RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('username', $subscription->pppoe_username)
            ->where('attribute', 'Framed-Route')
            ->where('value', $this->framedRouteValue($route, $subscription))
            ->delete();
    }

    private function syncFramedIpAddress(Subscription $subscription): void
    {
        RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('username', $subscription->pppoe_username)
            ->where('attribute', 'Framed-IP-Address')
            ->delete();

        RadiusReply::withoutGlobalScopes()->create([
            'tenant_id' => (string) $subscription->tenant_id,
            'username' => (string) $subscription->pppoe_username,
            'attribute' => 'Framed-IP-Address',
            'op' => ':=',
            'value' => (string) $subscription->ip_address,
        ]);
    }

    private function deleteFramedRoutes(Subscription $subscription): void
    {
        if (blank($subscription->tenant_id) || blank($subscription->pppoe_username)) {
            return;
        }

        RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('username', $subscription->pppoe_username)
            ->where('attribute', 'Framed-Route')
            ->delete();
    }

    private function framedRouteValue(SubscriptionIpRoute $route, Subscription $subscription): string
    {
        return $route->destinationAddress().' '.$subscription->ip_address.' 1';
    }

    private function ensureFramedRouteRepliesAllowMultipleRows(): void
    {
        if (! Schema::hasTable('radreply')) {
            return;
        }

        Schema::whenTableHasIndex(
            'radreply',
            ['tenant_id', 'username', 'attribute'],
            function (): void {
                Log::warning('Legacy radreply unique index detected. Dropping it so multiple Framed-Route rows can be synced.');

                Schema::table('radreply', function (Blueprint $table): void {
                    $table->dropUnique('radreply_tenant_id_username_attribute_unique');
                });
            },
            'unique'
        );
    }

    private function skipReason(?Subscription $subscription): ?string
    {
        if (! $subscription) {
            return 'Subscription is missing.';
        }

        if (blank($subscription->ip_address)) {
            return 'Subscription primary IP is missing.';
        }

        if (blank($subscription->pppoe_username)) {
            return 'Subscription PPP username is missing.';
        }

        $provisioningSkipReason = app(RadiusProvisioningService::class)->provisioningSkipReason($subscription);
        if ($provisioningSkipReason !== null) {
            return 'RADIUS provisioning skipped: '.$provisioningSkipReason;
        }

        return null;
    }

    private function markSkipped(SubscriptionIpRoute $route, string $reason): void
    {
        Log::info('Subscription IP route RADIUS sync skipped.', [
            'tenant_id' => $route->tenant_id,
            'subscription_id' => $route->subscription_id,
            'subscription_ip_route_id' => $route->id,
            'destination' => $route->destinationAddress(),
            'reason' => $reason,
        ]);

        $route->forceFill([
            'routeros_sync_status' => 'skipped',
            'routeros_sync_error' => $reason,
        ])->save();
    }
}
