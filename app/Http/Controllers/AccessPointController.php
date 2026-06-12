<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccessPoint\StoreAccessPointRequest;
use App\Http\Requests\AccessPoint\UpdateAccessPointRequest;
use App\Models\AccessPoint;
use App\Models\Router;
use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessPointController extends Controller
{
    /**
     * Display a listing of access points.
     */
    public function index(): View
    {
        return view('access-points.index');
    }

    /**
     * Get paginated access points data for AJAX requests.
     */
    public function data(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'vendor', 'site', 'frequency_band']);

        $accessPoints = AccessPoint::query()
            ->with(['siteRecord', 'router'])
            ->filter($filters)
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 50))
            ->through(fn (AccessPoint $ap) => [
                'id' => $ap->id,
                'name' => $ap->name,
                'model' => $ap->model,
                'vendor' => $ap->vendor,
                'mac_address' => $ap->mac_address,
                'ip_address' => $ap->ip_address,
                'site_id' => $ap->site_id,
                'site' => $ap->siteRecord?->name ?? '—',
                'router_id' => $ap->router_id,
                'router' => $ap->router?->name ?? '—',
                'frequency_band' => $ap->frequency_band ?? '—',
                'ssid' => $ap->ssid ?? '—',
                'status' => $ap->status ?? 'offline',
                'max_clients' => $ap->max_clients ?? 0,
                'connected_clients' => $ap->connected_clients ?? 0,
                'created_at' => $ap->created_at?->format('M d, Y'),
            ]);

        return response()->json([
            'accessPoints' => $accessPoints->items(),
            'pagination' => [
                'current_page' => $accessPoints->currentPage(),
                'last_page' => $accessPoints->lastPage(),
                'per_page' => $accessPoints->perPage(),
                'total' => $accessPoints->total(),
                'from' => $accessPoints->firstItem(),
                'to' => $accessPoints->lastItem(),
            ],
        ]);
    }

    /**
     * Get filter options for the index page.
     */
    public function filterOptions(): JsonResponse
    {
        return response()->json(AccessPoint::getFilterOptions());
    }

    /**
     * Get access point statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json(AccessPoint::getStats());
    }

    /**
     * Get access points for a specific router.
     */
    public function byRouter(Router $router): JsonResponse
    {
        $accessPoints = AccessPoint::where('router_id', $router->id)
            ->where('status', '!=', 'decommissioned')
            ->orderBy('name')
            ->get(['id', 'name', 'ssid', 'frequency_band', 'vendor']);

        return response()->json($accessPoints);
    }

    /**
     * Show the form for creating a new access point.
     */
    public function create(): View
    {
        return view('access-points.create', [
            'sites' => $this->activeSites(),
            'routers' => $this->activeRouters(),
        ]);
    }

    /**
     * Store a newly created access point in storage.
     */
    public function store(StoreAccessPointRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (auth()->check() && empty($validated['tenant_id'])) {
            $validated['tenant_id'] = auth()->user()->tenant_id;
        }

        $validated['status'] = $validated['status'] ?? 'offline';
        $validated['max_clients'] = $validated['max_clients'] ?? 0;
        $validated['connected_clients'] = $validated['connected_clients'] ?? 0;

        AccessPoint::create($validated);

        return redirect()
            ->route('access-points.index')
            ->with('success', 'Access point created successfully.');
    }

    /**
     * Display the specified access point.
     */
    public function show(AccessPoint $accessPoint): View
    {
        $this->authorizeTenantAccess($accessPoint);

        return view('access-points.show', [
            'accessPoint' => $accessPoint->load(['siteRecord', 'router', 'subscriptions.customer']),
        ]);
    }

    /**
     * Show the form for editing the specified access point.
     */
    public function edit(AccessPoint $accessPoint): View
    {
        $this->authorizeTenantAccess($accessPoint);

        return view('access-points.edit', [
            'accessPoint' => $accessPoint,
            'sites' => $this->activeSites(),
            'routers' => $this->activeRouters(),
        ]);
    }

    /**
     * Update the specified access point in storage.
     */
    public function update(UpdateAccessPointRequest $request, AccessPoint $accessPoint): RedirectResponse
    {
        $this->authorizeTenantAccess($accessPoint);

        $accessPoint->update($request->validated());

        return redirect()
            ->route('access-points.index')
            ->with('success', 'Access point updated successfully.');
    }

    /**
     * Remove the specified access point from storage.
     */
    public function destroy(Request $request, AccessPoint $accessPoint): JsonResponse|RedirectResponse
    {
        $this->authorizeTenantAccess($accessPoint);

        $accessPoint->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Access point deleted successfully.',
            ]);
        }

        return redirect()
            ->route('access-points.index')
            ->with('success', 'Access point deleted successfully.');
    }

    /**
     * Ensure the user has access to the access point's tenant.
     */
    protected function authorizeTenantAccess(AccessPoint $accessPoint): void
    {
        if (auth()->check() && auth()->user()->tenant_id && $accessPoint->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'You do not have access to this access point.');
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
     * @return Collection<int, Router>
     */
    protected function activeRouters(): Collection
    {
        return Router::query()
            ->orderBy('name')
            ->get(['id', 'name', 'ip_address']);
    }
}
