<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class BackfillCredentialHashes extends Command
{
    protected $signature = 'security:backfill-credential-hashes {--tenant= : Only backfill the specified tenant ID}';

    protected $description = 'Populate missing Subscription and Router password hashes without changing existing credentials';

    public function handle(TenantContext $tenantContext): int
    {
        $tenants = Tenant::query()->when($this->option('tenant'), fn ($query) => $query->whereKey($this->option('tenant')));

        if ($this->option('tenant') && ! $tenants->exists()) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        $count = 0;
        foreach ($tenants->lazyById(100) as $tenant) {
            $tenantContext->run((string) $tenant->id, function () use ($tenant, &$count): void {
                foreach ([Subscription::class => 'pppoe_password', Router::class => 'api_password'] as $model => $passwordColumn) {
                    $hashColumn = $passwordColumn.'_hash';
                    $query = $model::query()->where('tenant_id', $tenant->id)->whereNull($hashColumn);
                    if ($model === Subscription::class) {
                        $query->withTrashed();
                    }

                    foreach ($query->lazyById(100) as $record) {
                        if (blank($record->getAttribute($passwordColumn))) {
                            continue;
                        }

                        $hash = Hash::driver('argon2id')->make($record->getAttribute($passwordColumn));
                        $update = $model::query()->where('tenant_id', $tenant->id)->whereKey($record->getKey())
                            ->whereNull($hashColumn)->where($passwordColumn, $record->getRawOriginal($passwordColumn));
                        if ($model === Subscription::class) {
                            $update->withTrashed();
                        }
                        $count += $update->toBase()->update([$hashColumn => $hash]);
                    }
                }
            });
        }

        $this->info("Backfilled {$count} credential hashes.");

        return self::SUCCESS;
    }
}
