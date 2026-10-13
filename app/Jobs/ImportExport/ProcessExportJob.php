<?php

namespace App\Jobs\ImportExport;

use App\Models\ImportExportRun;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\ImportExport\SpreadsheetImportExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProcessExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $runId) {}

    public function handle(SpreadsheetImportExportService $service): void
    {
        $run = ImportExportRun::withoutGlobalScopes()->findOrFail($this->runId);
        $user = User::withoutGlobalScopes()->whereKey($run->user_id)->where('tenant_id', $run->tenant_id)->where('status', 'active')->firstOrFail();
        $guard = Auth::guard('web');
        $previousUser = $guard->hasUser() ? $guard->user() : null;
        $guard->setUser($user);
        try {
            $run->markProcessing();
            app(TenantContext::class)->run($run->tenant_id, fn () => $service->export($run->fresh()));
        } catch (Throwable $exception) {
            $run->markFailed('Processing failed. Contact support with the run ID.');

            throw $exception;
        } finally {
            $guard->forgetUser();
            if ($previousUser !== null) {
                $guard->setUser($previousUser);
            }
        }
    }
}
