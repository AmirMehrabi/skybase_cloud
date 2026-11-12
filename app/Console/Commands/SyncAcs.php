<?php

namespace App\Console\Commands;

use App\Jobs\SyncAcsInventory;
use App\Models\Tenant;
use Illuminate\Console\Command;

class SyncAcs extends Command
{
    protected $signature = 'acs:sync {--tenant= : Only synchronize this tenant}';

    protected $description = 'Queue inventory and action synchronization for ACS-enabled tenants';

    public function handle(): int
    {
        Tenant::query()->where('acs_enabled', true)->when($this->option('tenant'), fn ($q) => $q->whereKey($this->option('tenant')))->each(function (Tenant $tenant): void {
            SyncAcsInventory::dispatch($tenant->id);
        });

        return self::SUCCESS;
    }
}
