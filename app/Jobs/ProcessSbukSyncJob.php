<?php

namespace App\Jobs;

use App\Models\SbukSyncRun;
use App\Services\SbukPersonnelImportService;
use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSbukSyncJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 7200;

    public function __construct(public int $runId, public string $tenantId) {}

    public function uniqueId(): string
    {
        return $this->tenantId;
    }

    public function handle(SbukPersonnelImportService $service): void
    {
        $run = SbukSyncRun::query()->where('tenant_id', $this->tenantId)->findOrFail($this->runId);
        if ($run->status !== 'pending') {
            return;
        }
        app(TenantContext::class)->run($this->tenantId, fn () => $service->process($run));
    }

    public function failed(?\Throwable $exception): void
    {
        $run = SbukSyncRun::withoutGlobalScopes()->where('tenant_id', $this->tenantId)->find($this->runId);
        $run?->update([
            'status' => 'failed',
            'error_summary' => 'The queued import could not be completed.',
            'personnel_snapshot' => null,
            'completed_at' => now(),
        ]);
    }
}
