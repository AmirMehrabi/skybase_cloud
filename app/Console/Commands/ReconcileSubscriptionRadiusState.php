<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\RadiusProvisioningService;
use App\Services\SubscriptionIpRouteSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('subscriptions:reconcile-radius-state')]
#[Description('Reconcile RADIUS rows for active and suspended subscriptions')]
class ReconcileSubscriptionRadiusState extends Command
{
    public function handle(RadiusProvisioningService $radiusProvisioning, SubscriptionIpRouteSyncService $ipRouteSync): int
    {
        $processed = 0;
        $active = 0;
        $suspended = 0;
        $skipped = 0;
        $failed = 0;

        Log::info('Subscription RADIUS reconciliation started.');

        Subscription::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereIn('status', ['active', 'suspended'])
            ->with(['customer.organization', 'plan', 'ipRoutes'])
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($radiusProvisioning, $ipRouteSync, &$processed, &$active, &$suspended, &$skipped, &$failed): void {
                foreach ($subscriptions as $subscription) {
                    $processed++;

                    if ($subscription->status === 'active') {
                        $active++;
                    } elseif ($subscription->status === 'suspended') {
                        $suspended++;
                    }

                    try {
                        $skipReason = $radiusProvisioning->provisioningSkipReason($subscription);
                        $radiusProvisioning->syncSubscription($subscription);
                        $ipRouteSync->syncRoutes($subscription);

                        if ($skipReason !== null) {
                            $skipped++;
                        }
                    } catch (Throwable $exception) {
                        $failed++;

                        Log::error('Subscription RADIUS reconciliation failed.', [
                            'tenant_id' => $subscription->tenant_id,
                            'subscription_id' => $subscription->id,
                            'subscription_code' => $subscription->subscription_code,
                            'status' => $subscription->status,
                            'pppoe_username' => $subscription->pppoe_username,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            }, 'id');

        Log::info('Subscription RADIUS reconciliation completed.', [
            'processed' => $processed,
            'active' => $active,
            'suspended' => $suspended,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);

        $this->components->info("Subscription RADIUS reconciliation completed. Processed: {$processed}, active: {$active}, suspended: {$suspended}, skipped: {$skipped}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
