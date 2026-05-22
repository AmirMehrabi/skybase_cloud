<?php

namespace App\Console\Commands;

use App\Models\RadiusAccountingRecord;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

#[Signature('subscriptions:sync-connection-status')]
#[Description('Sync PPPoE subscription connection status from RADIUS accounting data')]
class SyncSubscriptionConnectionStatuses extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! Schema::hasTable('radacct')) {
            $this->components->warn('Skipping subscription connection sync because the radacct table does not exist.');

            return self::SUCCESS;
        }

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
                $openUsernames = RadiusAccountingRecord::query()
                    ->withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->openSession()
                    ->pluck('username')
                    ->filter()
                    ->flip();

                Subscription::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('id')
                    ->chunkById(200, function ($subscriptions) use (&$checked, &$online, &$offline, &$cleared, $openUsernames): void {
                        foreach ($subscriptions as $subscription) {
                            $checked++;

                            if (! $subscription->isPppoe() || blank($subscription->pppoe_username)) {
                                if ($subscription->connection_status !== null || $subscription->connection_status_checked_at !== null) {
                                    $subscription->forceFill([
                                        'connection_status' => null,
                                        'connection_status_checked_at' => now(),
                                    ])->saveQuietly();
                                }

                                $cleared++;

                                continue;
                            }

                            $isOnline = $openUsernames->has((string) $subscription->pppoe_username);

                            $subscription->forceFill([
                                'connection_status' => $isOnline ? 'online' : 'offline',
                                'connection_status_checked_at' => now(),
                            ])->saveQuietly();

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
