<?php

namespace App\Console\Commands;

use App\Services\Monitoring\VpnStatusCollector;
use Illuminate\Console\Command;
use Throwable;

class SyncVpnStatus extends Command
{
    protected $signature = 'vpn:sync-status';

    protected $description = 'Synchronize tenant VPN connections from a fresh OpenVPN status snapshot';

    public function handle(VpnStatusCollector $collector): int
    {
        try {
            $result = $collector->collect();
            $this->components->info("VPN status updated: {$result['updated']}; ambiguous identities skipped: {$result['ambiguous']}.");

            return $result['ambiguous'] > 0 ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
