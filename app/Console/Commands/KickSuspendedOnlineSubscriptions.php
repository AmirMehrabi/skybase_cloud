<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\SuspendedPppoeEnforcement;
use App\Services\TenantContext;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('subscriptions:kick-suspended-online {--tenant= : Only process subscriptions for a specific tenant ID} {--subscription= : Only process a specific subscription ID} {--dry-run : Show matching subscriptions without disconnecting them}')]
#[Description('Verify and enforce PPPoE suspension on assigned routers')]
class KickSuspendedOnlineSubscriptions extends Command
{
    public function handle(SuspendedPppoeEnforcement $enforcement, TenantContext $tenancy): int
    {
        $counts = ['checked' => 0, 'kicked' => 0, 'verified' => 0, 'skipped' => 0, 'failed' => 0];

        Tenant::query()->where('status', 'active')
            ->when($this->option('tenant'), fn ($query, string $tenantId) => $query->whereKey($tenantId))
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($enforcement, $tenancy, &$counts): void {
                $tenancy->run((string) $tenant->id, function () use ($tenant, $enforcement, &$counts): void {
                    $activeByRouter = [];
                    Subscription::query()->where('tenant_id', $tenant->id)
                        ->where('status', 'suspended')
                        ->where('connection_type', 'pppoe')
                        ->whereNotNull('pppoe_username')
                        ->where('pppoe_username', '!=', '')
                        ->when($this->option('subscription'), fn ($query, string $subscriptionId) => $query->whereKey($subscriptionId))
                        ->with('router')
                        ->chunkById(100, function ($subscriptions) use ($enforcement, &$activeByRouter, &$counts): void {
                            foreach ($subscriptions as $subscription) {
                                $counts['checked']++;
                                if ($this->option('dry-run')) {
                                    $this->components->info("Would check {$subscription->subscription_code} ({$subscription->pppoe_username}).");

                                    continue;
                                }

                                $routerId = $subscription->router_id;
                                if ($subscription->router && ! array_key_exists($routerId, $activeByRouter)) {
                                    try {
                                        $activeByRouter[$routerId] = array_fill_keys($enforcement->activeUsernames($subscription->router), true);
                                    } catch (Throwable) {
                                        $activeByRouter[$routerId] = null;
                                    }
                                }

                                $active = isset($activeByRouter[$routerId])
                                    ? isset($activeByRouter[$routerId][$subscription->pppoe_username])
                                    : null;

                                try {
                                    $result = $enforcement->enforce($subscription, $active);
                                    $count = match ($result->status) {
                                        'success' => $result->sessionsRemoved > 0 ? 'kicked' : 'verified',
                                        'skipped' => 'skipped',
                                        default => 'failed',
                                    };
                                } catch (Throwable $exception) {
                                    report($exception);
                                    $count = 'failed';
                                }
                                $counts[$count]++;
                            }
                        });
                });
            });

        Log::info('Suspended PPPoE enforcement completed.', $counts);
        $this->components->info("Suspended PPPoE enforcement completed. Checked: {$counts['checked']}, kicked: {$counts['kicked']}, verified: {$counts['verified']}, skipped: {$counts['skipped']}, failed: {$counts['failed']}.");

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
