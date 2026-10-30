<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\SkybaseBillingService;
use App\Services\TenantContext;
use Illuminate\Console\Command;

class BackfillSkybaseAccounts extends Command
{
    protected $signature = 'skybase:backfill-accounts';

    protected $description = 'Create unconfigured software accounts without inferring paid terms';

    public function handle(TenantContext $context, SkybaseBillingService $billing): int
    {
        if (! config('skybase_billing.enabled')) {
            $this->error('SaaS billing is disabled.');

            return self::FAILURE;
        }
        Tenant::query()->whereNull('archived_at')->chunkById(100, function ($tenants) use ($context, $billing): void {
            foreach ($tenants as $tenant) {
                $context->run($tenant->id, fn () => $billing->account($tenant->id));
            }
        });
        $this->info('Unconfigured accounts initialized; existing terms preserved.');

        return self::SUCCESS;
    }
}
