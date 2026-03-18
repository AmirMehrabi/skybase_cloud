<?php

namespace App\Http\Controllers;

use App\Http\Requests\Router\StoreRouterRequest;
use App\Http\Requests\Router\UpdateRouterRequest;
use App\Models\Router;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        $routers = Router::filter($filters)
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
                'site' => $router->site,
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
        return view('routers.create');
    }

    /**
     * Store a newly created router in storage.
     */
    public function store(StoreRouterRequest $request): JsonResponse
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

        return response()->json([
            'message' => 'Router created successfully.',
            'router' => $router,
        ], 201);
    }

    /**
     * Display the specified router.
     */
    public function show(Router $router): View
    {
        $this->authorizeTenantAccess($router);

        return view('routers.show', ['router' => $router]);
    }

    /**
     * Show the form for editing the specified router.
     */
    public function edit(Router $router): View
    {
        $this->authorizeTenantAccess($router);

        return view('routers.edit', ['router' => $router]);
    }

    /**
     * Update the specified router in storage.
     */
    public function update(UpdateRouterRequest $request, Router $router): JsonResponse
    {
        $this->authorizeTenantAccess($router);

        $validated = $request->validated();

        // Only update password if provided
        if (empty($validated['api_password'])) {
            unset($validated['api_password']);
        }

        $router->update($validated);

        return response()->json([
            'message' => 'Router updated successfully.',
            'router' => $router->fresh(),
        ]);
    }

    /**
     * Remove the specified router from storage.
     */
    public function destroy(Router $router): JsonResponse
    {
        $this->authorizeTenantAccess($router);

        $router->delete();

        return response()->json([
            'message' => 'Router deleted successfully.',
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
     * Display router sessions.
     */
    public function sessions(Router $router): View
    {
        return view('routers.sessions', compact('router'));
    }

    /**
     * Display router queues.
     */
    public function queues(Router $router): View
    {
        return view('routers.queues', compact('router'));
    }

    /**
     * Display router profiles.
     */
    public function profiles(Router $router): View
    {
        return view('routers.profiles', compact('router'));
    }

    /**
     * Display router interfaces.
     */
    public function interfaces(Router $router): View
    {
        return view('routers.interfaces', compact('router'));
    }

    /**
     * Display router IP pools.
     */
    public function ipPools(Router $router): View
    {
        return view('routers.ip-pools', compact('router'));
    }

    /**
     * Display router logs.
     */
    public function logs(Router $router): View
    {
        return view('routers.logs', compact('router'));
    }
}
