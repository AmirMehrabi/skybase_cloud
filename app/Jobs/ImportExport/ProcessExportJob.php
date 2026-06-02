<?php

namespace App\Jobs\ImportExport;

use App\Models\ImportExportRun;
use App\Support\ImportExport\SpreadsheetImportExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $runId) {}

    public function handle(SpreadsheetImportExportService $service): void
    {
        $run = ImportExportRun::query()->findOrFail($this->runId);
        $run->markProcessing();

        try {
            $service->export($run->fresh());
        } catch (Throwable $exception) {
            $run->markFailed($exception->getMessage());

            throw $exception;
        }
    }
}
