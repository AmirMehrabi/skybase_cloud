<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportExport\StoreImportRequest;
use App\Jobs\ImportExport\ProcessExportJob;
use App\Jobs\ImportExport\ProcessImportJob;
use App\Models\ImportExportRun;
use App\Support\ImportExport\ImportExportSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    public function exportPlans(Request $request): RedirectResponse
    {
        return $this->queueExport($request, ImportExportSchema::MODULE_PLANS, 'plans.index');
    }

    public function importPlans(StoreImportRequest $request): RedirectResponse
    {
        return $this->queueImport($request, ImportExportSchema::MODULE_PLANS, 'plans.index');
    }

    public function planRuns(Request $request): JsonResponse
    {
        return $this->runs($request, ImportExportSchema::MODULE_PLANS);
    }

    public function exportSubscriptions(Request $request): RedirectResponse
    {
        return $this->queueExport($request, ImportExportSchema::MODULE_SUBSCRIPTIONS, 'subscriptions.index');
    }

    public function importSubscriptions(StoreImportRequest $request): RedirectResponse
    {
        return $this->queueImport($request, ImportExportSchema::MODULE_SUBSCRIPTIONS, 'subscriptions.index');
    }

    public function subscriptionRuns(Request $request): JsonResponse
    {
        return $this->runs($request, ImportExportSchema::MODULE_SUBSCRIPTIONS);
    }

    public function show(string $module, ImportExportRun $run): View
    {
        $this->authorizeRun($module, $run);

        $rows = $run->rows()
            ->latest('row_number')
            ->paginate(50);

        return view('import-export.show', compact('module', 'run', 'rows'));
    }

    public function download(string $module, ImportExportRun $run): StreamedResponse
    {
        $this->authorizeRun($module, $run);

        abort_unless($run->direction === ImportExportRun::DIRECTION_EXPORT, 404);
        abort_unless($run->status === ImportExportRun::STATUS_COMPLETED, 404);
        abort_unless($run->file_path && Storage::disk($run->disk)->exists($run->file_path), 404);

        return Storage::disk($run->disk)->download($run->file_path, basename($run->file_path));
    }

    protected function queueExport(Request $request, string $module, string $redirectRoute): RedirectResponse
    {
        $run = ImportExportRun::query()->create([
            'tenant_id' => (string) $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'module' => $module,
            'direction' => ImportExportRun::DIRECTION_EXPORT,
            'status' => ImportExportRun::STATUS_QUEUED,
            'filters' => $this->filtersFor($module, $request),
            'disk' => 'local',
        ]);

        ProcessExportJob::dispatch($run->id);

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Export queued. You can download it from the activity panel when it completes.');
    }

    protected function queueImport(StoreImportRequest $request, string $module, string $redirectRoute): RedirectResponse
    {
        $tenantId = (string) $request->user()->tenant_id;
        $run = ImportExportRun::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'module' => $module,
            'direction' => ImportExportRun::DIRECTION_IMPORT,
            'status' => ImportExportRun::STATUS_QUEUED,
            'disk' => 'local',
            'original_filename' => $request->file('file')?->getClientOriginalName(),
        ]);

        $path = $request->file('file')->storeAs(
            ImportExportSchema::basePath($tenantId, $run->id),
            'import-'.$run->id.'.xlsx',
            'local',
        );

        $run->update(['file_path' => $path]);

        ProcessImportJob::dispatch($run->id);

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Import queued. Detailed progress is available in the activity panel.');
    }

    protected function runs(Request $request, string $module): JsonResponse
    {
        $runs = ImportExportRun::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('module', $module)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (ImportExportRun $run): array => [
                'id' => $run->id,
                'module' => $run->module,
                'direction' => $run->direction,
                'status' => $run->status,
                'original_filename' => $run->original_filename,
                'total_rows' => $run->total_rows,
                'processed_rows' => $run->processed_rows,
                'created_count' => $run->created_count,
                'updated_count' => $run->updated_count,
                'skipped_count' => $run->skipped_count,
                'failed_count' => $run->failed_count,
                'error' => $run->error,
                'created_at' => $run->created_at?->format('M d, Y H:i'),
                'finished_at' => $run->finished_at?->format('M d, Y H:i'),
                'report_url' => route('import-export.show', [$run->module, $run]),
                'download_url' => $run->direction === ImportExportRun::DIRECTION_EXPORT && $run->status === ImportExportRun::STATUS_COMPLETED
                    ? route('import-export.download', [$run->module, $run])
                    : null,
            ]);

        return response()->json(['runs' => $runs]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function filtersFor(string $module, Request $request): array
    {
        return match ($module) {
            ImportExportSchema::MODULE_PLANS => array_filter($request->only(['search', 'status', 'type', 'category', 'billing_cycle']), filled(...)),
            ImportExportSchema::MODULE_SUBSCRIPTIONS => array_filter($request->only(['search', 'status', 'plan', 'customer']), filled(...)),
            default => [],
        };
    }

    protected function authorizeRun(string $module, ImportExportRun $run): void
    {
        abort_unless($run->module === $module, 404);
        abort_unless((string) $run->tenant_id === (string) auth()->user()?->tenant_id, 403);
    }
}
