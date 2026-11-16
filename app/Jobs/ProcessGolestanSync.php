<?php

namespace App\Jobs;

use App\Models\GolestanSyncRun;
use App\Models\User;
use App\Services\GolestanSyncService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessGolestanSync implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public function __construct(public string $tenantId, public int $runId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->tenantId))->releaseAfter(30)->expireAfter(75)];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHour();
    }

    public function handle(GolestanSyncService $service, TenantContext $context): void
    {
        $context->run($this->tenantId, function () use ($service): void {
            $run = $this->runQuery()->findOrFail($this->runId);
            if (! $run->snapshot) {
                return;
            }
            $actor = User::query()->where('tenant_id', $this->tenantId)->find($run->user_id);
            $tenant = tenant();
            if (! config('services.golestan.enabled') || ! $actor?->isActive() || ! $actor->isAdmin()
                || ! $tenant->isActive() || $tenant->application_access_suspended || $tenant->archived_at) {
                $run->update(['status' => 'failed', 'snapshot' => null]);

                return;
            }

            $guard = auth('web');
            $previous = $guard->hasUser() ? $guard->user() : null;
            $guard->setUser($actor);
            try {
                $this->process($run, $service);
            } finally {
                $previous ? $guard->setUser($previous) : $guard->forgetUser();
            }
        });
    }

    private function process(GolestanSyncRun $run, GolestanSyncService $service): void
    {
        $snapshot = $run->snapshot;
        $run->update(['status' => 'processing']);
        foreach ($snapshot['rows'] as $index => $row) {
            try {
                DB::transaction(function () use ($index, $row, $snapshot, $service): void {
                    $locked = $this->runQuery()->lockForUpdate()->findOrFail($this->runId);
                    if (isset($locked->outcomes[$index])) {
                        return;
                    }
                    $settings = $service->settings($this->tenantId, $snapshot['settings']);
                    $outcomes = $locked->outcomes;
                    $outcomes[$index] = $service->apply($this->tenantId, $row, $settings);
                    $locked->update(['outcomes' => $outcomes]);
                }, 3);
            } catch (ValidationException $exception) {
                DB::transaction(function () use ($index, $exception): void {
                    $locked = $this->runQuery()->lockForUpdate()->findOrFail($this->runId);
                    $outcomes = $locked->outcomes;
                    $outcomes[$index] ??= ['status' => 'failed', 'message' => collect($exception->errors())->flatten()->first()];
                    $locked->update(['outcomes' => $outcomes]);
                }, 3);
            }
        }
        $run->refresh()->update([
            'status' => collect($run->outcomes)->contains('status', 'failed') ? 'completed_with_errors' : 'completed',
            'snapshot' => null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        GolestanSyncRun::withoutGlobalScopes()->where('tenant_id', $this->tenantId)->whereKey($this->runId)
            ->whereIn('status', ['pending', 'processing'])->update(['status' => 'failed', 'snapshot' => null]);
    }

    private function runQuery(): Builder
    {
        return GolestanSyncRun::query()->where('tenant_id', $this->tenantId);
    }
}
