<?php

namespace App\Jobs;

use App\Models\BulkDeletionRun;
use App\Services\BulkDeletionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class BulkDeleteModelsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public int $runId) {}

    public function handle(BulkDeletionService $service): void
    {
        try {
            $run = BulkDeletionRun::withoutGlobalScopes()->findOrFail($this->runId);
            $run->markProcessing();

            $summary = [];
            $summary = $service->process($run);
            $run->markCompleted(
                $summary,
                $summary['processed_count'] ?? 0,
                $summary['deleted_count'] ?? 0,
                $summary['failed_count'] ?? 0,
            );
            $service->logRun($run, 'completed', $summary);
        } catch (Throwable $exception) {
            if (isset($run)) {
                $summary ??= [];

                $run->markFailed(
                    $exception->getMessage(),
                    $summary,
                    $summary['processed_count'] ?? 0,
                    $summary['deleted_count'] ?? 0,
                    $summary['failed_count'] ?? 0,
                );
                $service->logRun($run, 'failed', $summary, $exception->getMessage());
            }

            report($exception);
        }
    }
}
