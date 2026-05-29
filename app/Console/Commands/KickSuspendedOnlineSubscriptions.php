<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\RadiusProvisioningService;
use App\Services\SubscriptionSessionDisconnectService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('subscriptions:kick-suspended-online {--tenant= : Only process subscriptions for a specific tenant ID} {--subscription= : Only process a specific subscription ID} {--dry-run : Show matching subscriptions without disconnecting them}')]
#[Description('Kick online PPPoE users whose subscriptions are suspended')]
class KickSuspendedOnlineSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RadiusProvisioningService $radiusProvisioning, SubscriptionSessionDisconnectService $disconnectService): int
    {
        $checked = 0;
        $kicked = 0;
        $skipped = 0;
        $failed = 0;
        $dryRun = (bool) $this->option('dry-run');

        Log::info('Suspended online subscription kick started.', [
            'tenant_id' => $this->option('tenant'),
            'subscription_id' => $this->option('subscription'),
            'dry_run' => $dryRun,
        ]);

        Tenant::query()
            ->where('status', 'active')
            ->when($this->option('tenant'), fn ($query, string $tenantId) => $query->where('id', $tenantId))
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($radiusProvisioning, $disconnectService, $dryRun, &$checked, &$kicked, &$skipped, &$failed): void {
                Subscription::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'suspended')
                    ->where('connection_type', 'pppoe')
                    ->where('connection_status', 'online')
                    ->whereNotNull('pppoe_username')
                    ->when($this->option('subscription'), fn ($query, string $subscriptionId) => $query->whereKey($subscriptionId))
                    ->with(['router', 'customer.organization', 'plan'])
                    ->orderBy('id')
                    ->chunkById(100, function ($subscriptions) use ($radiusProvisioning, $disconnectService, $dryRun, &$checked, &$kicked, &$skipped, &$failed): void {
                        foreach ($subscriptions as $subscription) {
                            $checked++;

                            if ($dryRun) {
                                $this->components->info("Would kick {$subscription->subscription_code} ({$subscription->pppoe_username}).");

                                continue;
                            }

                            $radiusProvisioning->syncSubscription($subscription);
                            $result = $disconnectService->disconnect($subscription);
                            $disconnectService->recordActivity($subscription, $result);

                            if ($result->wasSuccessful()) {
                                $subscription->forceFill([
                                    'connection_status' => 'offline',
                                    'connection_status_checked_at' => now(),
                                ])->saveQuietly();

                                $kicked++;

                                continue;
                            }

                            $result->status === 'skipped' ? $skipped++ : $failed++;

                            Log::warning('Suspended online subscription kick did not disconnect session.', [
                                'tenant_id' => $subscription->tenant_id,
                                'subscription_id' => $subscription->id,
                                'subscription_code' => $subscription->subscription_code,
                                'pppoe_username' => $subscription->pppoe_username,
                                ...$result->context(),
                            ]);
                        }
                    }, 'id');
            });

        Log::info('Suspended online subscription kick completed.', [
            'checked' => $checked,
            'kicked' => $kicked,
            'skipped' => $skipped,
            'failed' => $failed,
            'dry_run' => $dryRun,
        ]);

        $this->components->info("Suspended online subscription kick completed. Checked: {$checked}, kicked: {$kicked}, skipped: {$skipped}, failed: {$failed}.");

        return ($failed + $skipped) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
