<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\IpdrClient;
use App\Services\IpdrInventoryService;
use App\Services\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncIpdrInventory extends Command
{
    protected $signature = 'ipdr:sync {--tenant= : Restrict synchronization to one tenant}';

    protected $description = 'Synchronize historical subscriber address/session inventory to IPDR';

    public function handle(IpdrInventoryService $inventory, IpdrClient $client): int
    {
        if (! config('ipdr.enabled')) {
            $this->info('IPDR integration is disabled.');

            return self::SUCCESS;
        }

        return Cache::lock('ipdr:inventory-sync', 1800)->get(function () use ($inventory, $client): int {
            $failed = false;
            foreach (Tenant::query()->when($this->option('tenant'), fn ($q, $id) => $q->whereKey($id))->cursor() as $tenant) {
                try {
                    $count = app(TenantContext::class)->run((string) $tenant->id, function () use ($inventory, $client, $tenant): int {
                        Subscription::query()->where('tenant_id', $tenant->id)->withTrashed()->chunkById(100, function ($subscriptions) use ($inventory): void {
                            foreach ($subscriptions as $subscription) {
                                $inventory->subscription($subscription);
                            }
                        });
                        $inventory->radius((string) $tenant->id);

                        return $inventory->deliver((string) $tenant->id, $client);
                    });
                    $this->info("Tenant {$tenant->id}: delivered {$count} assignment revisions.");
                } catch (Throwable $exception) {
                    report($exception);
                    $this->error("Tenant {$tenant->id}: IPDR synchronization failed; pending inventory retained for retry.");
                    $failed = true;
                }
            }

            return $failed ? self::FAILURE : self::SUCCESS;
        }) ?? self::SUCCESS;
    }
}
