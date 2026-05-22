<?php

namespace App\Console\Commands;

use App\Models\RadiusAccountingRecord;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('subscriptions:sync-connection-status')]
#[Description('Sync PPPoE subscription connection status from RADIUS accounting data')]
class SyncSubscriptionConnectionStatuses extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startedAt = now();
        $checked = 0;
        $online = 0;
        $offline = 0;
        $cleared = 0;

        Log::info('Subscription connection status sync started.');

        Tenant::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->each(function (Tenant $tenant) use (&$checked, &$online, &$offline, &$cleared): void {
                Subscription::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('id')
                    ->chunkById(200, function ($subscriptions) use (&$checked, &$online, &$offline, &$cleared, $tenant): void {
                        foreach ($subscriptions as $subscription) {
                            $checked++;

                            if (! $subscription->isPppoe() || blank($subscription->pppoe_username)) {
                                if ($subscription->connection_status !== null || $subscription->connection_status_checked_at !== null) {
                                    $subscription->forceFill([
                                        'connection_status' => null,
                                        'connection_status_checked_at' => now(),
                                    ])->save();
                                }

                                $cleared++;

                                continue;
                            }

                            $isOnline = RadiusAccountingRecord::query()
                                ->withoutGlobalScopes()
                                ->where('tenant_id', $tenant->id)
                                ->forUsername((string) $subscription->pppoe_username)
                                ->openSession()
                                ->exists();

                            $subscription->forceFill([
                                'connection_status' => $isOnline ? 'online' : 'offline',
                                'connection_status_checked_at' => now(),
                            ])->save();

                            $isOnline ? $online++ : $offline++;
                        }
                    });
            });

        Log::info('Subscription connection status sync completed.', [
            'checked' => $checked,
            'online' => $online,
            'offline' => $offline,
            'cleared' => $cleared,
            'duration_ms' => round($startedAt->diffInRealMilliseconds(now()), 2),
        ]);

        $this->components->info("Subscription connection status sync completed. Checked: {$checked}, online: {$online}, offline: {$offline}, cleared: {$cleared}.");

        return self::SUCCESS;
    }
}
