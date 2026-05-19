<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\Ldap\LdapSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncTenantLdap implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $tenantId) {}

    public function handle(LdapSyncService $sync): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (! $tenant) {
            return;
        }

        $sync->syncTenant($tenant);
    }
}
