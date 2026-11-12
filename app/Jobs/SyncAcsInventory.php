<?php

namespace App\Jobs;

use App\Models\AcsConnection;
use App\Models\Tenant;
use App\Services\AcsService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SyncAcsInventory implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public string $tenantId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('acs-sync:'.$this->tenantId))->expireAfter(240)->dontRelease()];
    }

    public function handle(AcsService $service): void
    {
        if (! Tenant::query()->find($this->tenantId)?->acs_enabled) {
            return;
        }
        app(TenantContext::class)->run($this->tenantId, function () use ($service): void {
            $connection = AcsConnection::query()->first();
            if ($connection) {
                try {
                    $service->sync($connection);
                } catch (\Throwable) {
                    $connection->update(['health' => 'unreachable']);
                    throw new \RuntimeException('ACS synchronization failed. Check connection settings and server availability.');
                }
            }
        });
    }
}
