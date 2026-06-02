<?php

namespace App\Http\Controllers;

use App\Http\Requests\Router\SetupNetflowRequest;
use App\Http\Requests\Router\StoreRouterRequest;
use App\Http\Requests\Router\UpdateRouterRequest;
use App\Models\NetflowFlow;
use App\Models\Router;
use App\Models\Site;
use App\Services\Monitoring\RrdToolService;
use App\Services\Netflow\NetflowSummaryService;
use App\Services\RouterOs\RouterOsTrafficFlowService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class RouterController extends Controller
{
    /**
     * Display a listing of routers.
     */
    public function index(): View
    {
        return view('routers.index');
    }

    /**
     * Get paginated routers data for AJAX requests.
     */
    public function data(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'vendor', 'site']);

        $routers = Router::query()
            ->with('siteRecord')
            ->filter($filters)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15))
            ->through(fn ($router) => [
                'id' => $router->id,
                'name' => $router->name,
                'model' => $router->model,
                'vendor' => $router->vendor,
                'ip_address' => $router->ip_address,
                'api_port' => $router->api_port,
                'ssh_port' => $router->ssh_port,
                'location' => $router->location,
                'site_id' => $router->site_id,
                'site' => $router->siteRecord?->name ?? $router->site,
                'status' => $router->status ?? 'offline',
                'version' => $router->version,
                'uptime' => $router->uptime,
                'cpu_usage' => $router->cpu_usage ?? 0,
                'memory_usage' => $router->memory_usage ?? 0,
                'active_sessions_count' => $router->active_sessions_count ?? 0,
                'total_customers' => $router->total_customers ?? 0,
                'enable_monitoring' => $router->enable_monitoring,
                'enable_provisioning' => $router->enable_provisioning,
                'created_at' => $router->created_at?->format('M d, Y'),
            ]);

        return response()->json([
            'routers' => $routers->items(),
            'pagination' => [
                'current_page' => $routers->currentPage(),
                'last_page' => $routers->lastPage(),
                'per_page' => $routers->perPage(),
                'total' => $routers->total(),
                'from' => $routers->firstItem(),
                'to' => $routers->lastItem(),
            ],
        ]);
    }

    /**
     * Get filter options for the routers index page.
     */
    public function filterOptions(): JsonResponse
    {
        return response()->json(Router::getFilterOptions());
    }

    /**
     * Get router statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json(Router::getStats());
    }

    /**
     * Show the form for creating a new router.
     */
    public function create(): View
    {
        return view('routers.create', [
            'sites' => $this->activeSites(),
        ]);
    }

    /**
     * Store a newly created router in storage.
     */
    public function store(StoreRouterRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        // Set tenant if not provided
        if (auth()->check() && empty($validated['tenant_id'])) {
            $validated['tenant_id'] = auth()->user()->tenant_id;
        }

        // Set default values
        $validated['status'] = $validated['status'] ?? 'offline';
        $validated['cpu_usage'] = 0;
        $validated['memory_usage'] = 0;
        $validated['active_sessions_count'] = 0;
        $validated['total_customers'] = 0;

        $router = Router::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Router created successfully.',
                'router' => $router,
            ], 201);
        }

        return redirect()
            ->route('routers.index')
            ->with('success', 'Router created successfully.');
    }

    /**
     * Display the specified router.
     */
    public function show(Router $router, NetflowSummaryService $netflowSummary): View
    {
        $this->authorizeTenantAccess($router);

        return view('routers.show', [
            'router' => $router,
            'netflowSummary' => $netflowSummary->forRouter($router),
        ]);
    }

    /**
     * Show the form for editing the specified router.
     */
    public function edit(Router $router): View
    {
        $this->authorizeTenantAccess($router);

        return view('routers.edit', [
            'router' => $router,
            'sites' => $this->activeSites(),
        ]);
    }

    /**
     * Update the specified router in storage.
     */
    public function update(UpdateRouterRequest $request, Router $router): JsonResponse|RedirectResponse
    {
        $this->authorizeTenantAccess($router);

        $validated = $request->validated();

        // Only update password if provided
        if (empty($validated['api_password'])) {
            unset($validated['api_password']);
        }

        $router->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Router updated successfully.',
                'router' => $router->fresh(),
            ]);
        }

        return redirect()
            ->route('routers.index')
            ->with('success', 'Router updated successfully.');
    }

    /**
     * Remove the specified router from storage.
     */
    public function destroy(Request $request, Router $router): JsonResponse|RedirectResponse
    {
        $this->authorizeTenantAccess($router);

        $router->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Router deleted successfully.',
            ]);
        }

        return redirect()
            ->route('routers.index')
            ->with('success', 'Router deleted successfully.');
    }

    public function setupNetflow(SetupNetflowRequest $request, Router $router, RouterOsTrafficFlowService $trafficFlow): JsonResponse
    {
        $this->authorizeTenantAccess($router);

        if (! $router->isMikrotik()) {
            return response()->json([
                'message' => 'NetFlow setup is only available for MikroTik routers.',
            ], 422);
        }

        $validated = $request->validated();
        $enabled = (bool) $validated['netflow_enabled'];

        if ($enabled && (! $router->api_username || ! $router->api_password)) {
            return response()->json([
                'message' => 'RouterOS API credentials are required before NetFlow can be configured.',
            ], 422);
        }

        $router->forceFill([
            'netflow_enabled' => $enabled,
            'netflow_collector_host' => $enabled ? $validated['netflow_collector_host'] : null,
            'netflow_collector_port' => $validated['netflow_collector_port'] ?? config('netflow.collector_port'),
            'netflow_version' => $validated['netflow_version'] ?? 9,
            'netflow_interfaces' => $validated['netflow_interfaces'] ?? 'all',
            'netflow_sampling_interval' => $validated['netflow_sampling_interval'] ?? 1,
            'netflow_setup_status' => 'pending',
            'netflow_error' => null,
        ])->save();

        try {
            $trafficFlow->configure($router->fresh());

            $router->forceFill([
                'netflow_setup_status' => $enabled ? 'configured' : 'disabled',
                'netflow_last_setup_at' => now(),
                'netflow_error' => null,
            ])->save();

            return response()->json([
                'message' => $enabled ? 'NetFlow has been configured on the MikroTik router.' : 'NetFlow has been disabled on the MikroTik router.',
                'router' => $router->fresh(),
            ]);
        } catch (Throwable $exception) {
            $router->forceFill([
                'netflow_setup_status' => 'failed',
                'netflow_error' => $exception->getMessage(),
            ])->save();

            return response()->json([
                'message' => 'NetFlow setup failed: '.$exception->getMessage(),
            ], 422);
        }
    }

    public function testNetflow(Router $router): JsonResponse
    {
        $this->authorizeTenantAccess($router);

        if (! $router->netflow_enabled) {
            return response()->json([
                'message' => 'Enable and configure NetFlow before testing packet collection.',
            ], 422);
        }

        $latestFlow = NetflowFlow::query()
            ->where('tenant_id', $router->tenant_id)
            ->where('router_id', $router->id)
            ->where('received_at', '>=', now()->subSeconds((int) config('netflow.test_window_seconds')))
            ->latest('received_at')
            ->first();

        $router->forceFill([
            'netflow_test_status' => $latestFlow ? 'received' : 'timeout',
            'netflow_last_tested_at' => now(),
            'netflow_last_packet_at' => $latestFlow?->received_at ?? $router->netflow_last_packet_at,
            'netflow_error' => $latestFlow ? null : 'No NetFlow packets were received during the test window.',
        ])->save();

        return response()->json([
            'message' => $latestFlow ? 'NetFlow packets are being received.' : 'No recent NetFlow packets were found for this router.',
            'status' => $latestFlow ? 'received' : 'timeout',
            'last_packet_at' => $latestFlow?->received_at?->diffForHumans(),
        ], $latestFlow ? 200 : 422);
    }

    public function netflowData(Router $router, NetflowSummaryService $netflowSummary): JsonResponse
    {
        $this->authorizeTenantAccess($router);

        return response()->json($netflowSummary->forRouter($router->fresh()));
    }

    public function monitoringData(Request $request, Router $router, RrdToolService $rrdTool): JsonResponse
    {
        $this->authorizeTenantAccess($router);

        try {
            $chartData = collect($rrdTool->routerHealthSeries($router, (string) $request->query('range', '24h')))
                ->map(fn (array $row): array => [
                    ...$row,
                    'time' => date('H:i', (int) $row['timestamp']),
                ])
                ->values();
        } catch (Throwable) {
            $chartData = collect();
        }

        return response()->json([
            'chartData' => $chartData,
        ]);
    }

    /**
     * Ensure the user has access to the router's tenant.
     */
    protected function authorizeTenantAccess(Router $router): void
    {
        if (auth()->check() && auth()->user()->tenant_id && $router->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'You do not have access to this router.');
        }
    }

    /**
     * @return Collection<int, Site>
     */
    protected function activeSites(): Collection
    {
        return Site::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /**
     * Display router sessions.
     */
    public function sessions(Router $router): View
    {
        $this->authorizeTenantAccess($router);

        return view('routers.sessions', compact('router'));
    }

    /**
     * Display router queues.
     */
    public function queues(Router $router): View
    {
        $this->authorizeTenantAccess($router);

        return view('routers.queues', compact('router'));
    }

    /**
     * Display router profiles.
     */
    public function profiles(Router $router): View
    {
        $this->authorizeTenantAccess($router);

        return view('routers.profiles', compact('router'));
    }

    /**
     * Display router interfaces.
     */
    public function interfaces(Router $router): View
    {
        $this->authorizeTenantAccess($router);

        return view('routers.interfaces', compact('router'));
    }

    /**
     * Display router IP pools.
     */
    public function ipPools(Router $router): View
    {
        $this->authorizeTenantAccess($router);

        return view('routers.ip-pools', compact('router'));
    }

    /**
     * Display router logs.
     */
    public function logs(Router $router): View
    {
        $this->authorizeTenantAccess($router);

        return view('routers.logs', compact('router'));
    }
}
