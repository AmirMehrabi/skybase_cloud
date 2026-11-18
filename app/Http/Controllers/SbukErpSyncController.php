<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sbuk\PreviewRequest;
use App\Http\Requests\Sbuk\StartRequest;
use App\Jobs\ProcessSbukSyncJob;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\SbukSyncRun;
use App\Services\SbukErpClient;
use App\Services\SbukPersonnelImportService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use RuntimeException;

class SbukErpSyncController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);
        $tenantId = (string) $request->user()->tenant_id;
        $runs = SbukSyncRun::query()->where('tenant_id', $tenantId)->latest()->limit(10)->get();

        return response()->view('modules.integrations.sbuk-erp', [
            'plans' => Plan::query()->where('tenant_id', $tenantId)->active()->ordered()->get(['id', 'name']),
            'routers' => Router::query()->where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'status']),
            'ipPools' => IpPool::query()->where('tenant_id', $tenantId)->active()->orderBy('name')->get(['id', 'name', 'router_id', 'available_ips']),
            'runs' => $runs,
            'activeRun' => $runs->first(fn (SbukSyncRun $run): bool => in_array($run->status, ['pending', 'fetching', 'validating', 'processing'], true)),
            'enabled' => (bool) config('services.sbuk_erp.enabled'),
            'configured' => filled(config('services.sbuk_erp.token')),
        ])->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function fetch(Request $request, SbukErpClient $client): JsonResponse
    {
        $this->authorizeAdmin($request);

        try {
            $records = $client->personnel();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $token = (string) Str::uuid();
        Cache::put($this->datasetKey($token), Crypt::encryptString(json_encode([
            'tenant_id' => (string) $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'records' => $records,
        ], JSON_THROW_ON_ERROR)), now()->addMinutes(20));

        return response()->json([
            'dataset_token' => $token,
            'rows' => $this->displayRows($records),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function preview(PreviewRequest $request, SbukPersonnelImportService $service): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;
        $validated = $request->validated();
        $encrypted = Cache::get($this->datasetKey($validated['dataset_token']));
        abort_unless(is_string($encrypted), 422, 'The ERP records have expired. Fetch them again.');
        $dataset = json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
        abort_unless($dataset['tenant_id'] === $tenantId && (int) $dataset['user_id'] === (int) $request->user()->id, 403);

        $filters = $validated['filters'] ?? [];
        $displayRows = $this->displayRows($dataset['records']);
        $matched = array_filter($displayRows, function (array $row) use ($filters): bool {
            foreach (['employment_type', 'unit_title', 'employment_status'] as $field) {
                if (filled($filters[$field] ?? null) && $row[$field] !== $filters[$field]) {
                    return false;
                }
            }

            $search = trim($filters['search'] ?? '');
            if ($search === '') {
                return true;
            }

            foreach ($row as $value) {
                if (is_string($value) && mb_stripos($value, $search) !== false) {
                    return true;
                }
            }

            return false;
        });
        $rawRecords = array_values(array_intersect_key($dataset['records'], $matched));
        abort_if($rawRecords === [], 422, 'No ERP records match the selected filters.');

        $plan = Plan::query()->where('tenant_id', $tenantId)->active()->findOrFail($validated['plan_id']);
        $router = Router::query()->where('tenant_id', $tenantId)->findOrFail($validated['router_id']);
        $pool = IpPool::query()->where('tenant_id', $tenantId)->active()->findOrFail($validated['ip_pool_id']);
        abort_unless($router->status === 'online', 422, 'Select an online router.');
        $poolSupportsRouter = (int) $pool->router_id === (int) $router->id
            || $pool->all_devices
            || $pool->routers()->where('routers.tenant_id', $tenantId)->whereKey($router->id)->exists();
        abort_unless($poolSupportsRouter, 422, 'The selected IP pool is not available on the selected router.');

        $preview = $service->preview($rawRecords, $tenantId, (int) $plan->id, (int) $router->id, (int) $pool->id);
        $preview['summary']['available_ip_addresses'] = $pool->availableAddresses()->count();
        $preview['summary']['estimated_ip_allocations'] = $preview['summary']['new_subscriptions'];
        $previewToken = (string) Str::uuid();
        Cache::put($this->previewKey($previewToken), Crypt::encryptString(json_encode([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'plan_id' => (int) $plan->id,
            'router_id' => (int) $router->id,
            'ip_pool_id' => (int) $pool->id,
            'pppoe_mapping' => [
                'username' => $validated['pppoe_username_source'] ?? 'mobile',
                'password' => $validated['pppoe_password_source'] ?? 'national_id',
            ],
            'existing_records' => [
                'customers' => $validated['existing_customers'] ?? 'skip',
                'subscriptions' => $validated['existing_subscriptions'] ?? 'skip',
            ],
            'records' => $preview['records'],
            'summary' => $preview['summary'],
        ], JSON_THROW_ON_ERROR)), now()->addMinutes(20));

        return response()->json([
            'preview_token' => $previewToken,
            'summary' => $preview['summary'],
            'rows' => $this->displayRows($rawRecords, $preview['records']),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function start(StartRequest $request): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;
        $key = $this->previewKey($request->validated('preview_token'));
        $encrypted = Cache::pull($key);
        abort_unless(is_string($encrypted), 422, 'This preview has expired. Fetch a new preview before starting a synchronization.');
        $snapshot = json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
        abort_unless($snapshot['tenant_id'] === $tenantId && (int) $snapshot['user_id'] === (int) $request->user()->id, 403);

        try {
            return Cache::lock('sbuk-erp-sync-start:'.$tenantId, 15)->block(5, function () use ($request, $snapshot, $tenantId): JsonResponse {
                $activeRun = SbukSyncRun::query()->where('tenant_id', $tenantId)
                    ->whereIn('status', ['pending', 'fetching', 'validating', 'processing'])->exists();
                abort_if($activeRun, 409, 'An SBUK synchronization is already running for this tenant.');
                $run = SbukSyncRun::query()->create([
                    'tenant_id' => $tenantId,
                    'triggered_by' => $request->user()->id,
                    'plan_id' => $snapshot['plan_id'],
                    'router_id' => $snapshot['router_id'],
                    'ip_pool_id' => $snapshot['ip_pool_id'],
                    'status' => 'pending',
                    'total_records' => $snapshot['summary']['total_records'],
                    'valid_records' => $snapshot['summary']['valid_records'],
                    'personnel_snapshot' => json_encode($snapshot['records'], JSON_THROW_ON_ERROR),
                    'preview_summary' => $snapshot['summary'] + [
                        'pppoe_mapping' => $snapshot['pppoe_mapping'] ?? [],
                        'existing_records' => $snapshot['existing_records'] ?? [],
                    ],
                ]);
                ProcessSbukSyncJob::dispatch($run->id, $tenantId);

                return response()->json(['run_id' => $run->id, 'status_url' => route('integrations.sbuk-erp.status', $run)]);
            });
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'An SBUK synchronization is already starting.'], 409);
        }
    }

    public function status(Request $request, SbukSyncRun $run): JsonResponse
    {
        $this->authorizeAdmin($request);
        abort_unless((string) $run->tenant_id === (string) $request->user()->tenant_id, 404);

        return response()->json($run->only([
            'id', 'status', 'total_records', 'valid_records', 'processed_records', 'created_customers',
            'matched_customers', 'created_subscriptions', 'skipped_subscriptions', 'failed_records', 'error_summary', 'started_at', 'completed_at',
        ]) + [
            'updated_subscriptions' => $run->records()->where('outcome', 'subscription_updated')->count(),
            'records' => $run->records()->latest('row_number')->limit(100)->get(['row_number', 'outcome', 'detail']),
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function displayRows(array $records, array $normalized = []): array
    {
        $rows = [];
        foreach ($records as $index => $record) {
            $rows[] = [
                'first_name' => (string) ($record['FNAME'] ?? ''),
                'last_name' => (string) ($record['LNAME'] ?? ''),
                'mobile' => (string) ($record['MOBILE'] ?? ''),
                'national_id' => (string) ($record['NATIONALID'] ?? ''),
                'external_index' => is_scalar($record['index_'] ?? null) ? (string) $record['index_'] : null,
                'employment_type' => is_scalar($record['EMPLOYTYPE'] ?? null) ? trim((string) $record['EMPLOYTYPE']) : null,
                'unit_title' => is_scalar($record['TITLE_UNIT'] ?? null) ? trim((string) $record['TITLE_UNIT']) : null,
                'employment_status' => is_scalar($record['EMPLOY_STATE'] ?? null) ? trim((string) $record['EMPLOY_STATE']) : null,
                'error' => $normalized[$index]['error'] ?? null,
            ];
        }

        return $rows;
    }

    private function datasetKey(string $token): string
    {
        return 'sbuk-erp-dataset:'.hash('sha256', $token);
    }

    private function previewKey(string $token): string
    {
        return 'sbuk-erp-preview:'.hash('sha256', $token);
    }
}
