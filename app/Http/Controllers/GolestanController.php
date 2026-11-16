<?php

namespace App\Http\Controllers;

use App\Http\Requests\GolestanSearchRequest;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Services\GolestanService;
use App\Services\GolestanSyncService;
use App\Support\GolestanFilters;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class GolestanController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return $this->page();
    }

    public function search(GolestanSearchRequest $request, GolestanService $service, GolestanSyncService $sync): Response
    {
        $result = null;
        $error = null;
        try {
            $result = $service->search($request->validated());
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }

        $syncData = [];
        if (! empty($result['records'])) {
            $tenantId = (string) tenant()->id;
            $credentialOptions = $sync->credentialFieldOptions($result['records']);
            $syncData = [
                'credentialOptions' => $credentialOptions,
                'defaultCredentialFields' => GolestanSyncService::DEFAULT_CREDENTIAL_FIELDS,
                'searchToken' => $sync->remember('search', [
                    'tenant_id' => $tenantId, 'user_id' => (int) $request->user()->id,
                    'credential_fields' => array_keys($credentialOptions),
                    'students' => array_map($sync->normalize(...), $result['records']),
                ]),
                'plans' => Plan::query()->where('tenant_id', $tenantId)->active()->ordered()->pluck('name', 'id'),
                'routers' => Router::query()->where('tenant_id', $tenantId)->orderBy('name')->pluck('name', 'id'),
                'pools' => IpPool::query()->where('tenant_id', $tenantId)->whereIn('status', ['active', 'exhausted'])->with('routers:id')->orderBy('name')->get()
                    ->map(fn (IpPool $pool): array => [
                        'id' => $pool->id, 'name' => $pool->name, 'all' => $pool->all_devices,
                        'routers' => [$pool->router_id, ...$pool->routers->modelKeys()],
                    ])->values(),
            ];
        }

        return $this->page([
            'filters' => $request->validated(),
            'requestXml' => $service->requestXml($request->validated(), redactPassword: true),
            'result' => $result,
            'serviceError' => $error,
        ] + $syncData);
    }

    /** @param array<string, mixed> $data */
    private function page(array $data = []): Response
    {
        return response()->view('modules.golestan.index', array_merge([
            'filters' => [], 'filterFields' => GolestanFilters::fields(), 'result' => null, 'serviceError' => null, 'requestXml' => null,
        ], $data))->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
