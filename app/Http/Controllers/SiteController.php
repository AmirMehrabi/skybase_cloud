<?php

namespace App\Http\Controllers;

use App\Http\Requests\Site\StoreSiteRequest;
use App\Http\Requests\Site\UpdateSiteRequest;
use App\Models\Site;
use App\Models\UserGroup;
use App\Services\UserGroupAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(): View
    {
        return view('sites.index');
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status']);

        $sites = Site::query()
            ->filter($filters)
            ->withCount([
                'routers',
                'routers as online_routers_count' => fn ($query) => $query->where('status', 'online'),
                'routers as offline_routers_count' => fn ($query) => $query->where('status', 'offline'),
            ])
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15))
            ->through(fn (Site $site) => $this->siteRow($site));

        return response()->json([
            'sites' => $sites->items(),
            'pagination' => [
                'current_page' => $sites->currentPage(),
                'last_page' => $sites->lastPage(),
                'per_page' => $sites->perPage(),
                'total' => $sites->total(),
                'from' => $sites->firstItem(),
                'to' => $sites->lastItem(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(Site::getStats());
    }

    public function mapData(): JsonResponse
    {
        $sites = Site::query()
            ->withCount([
                'routers',
                'routers as online_routers_count' => fn ($query) => $query->where('status', 'online'),
                'routers as offline_routers_count' => fn ($query) => $query->where('status', 'offline'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Site $site): array => [
                ...$this->siteRow($site),
                'health' => $this->siteHealth($site),
                'show_url' => route('sites.show', $site),
                'edit_url' => route('sites.edit', $site),
            ]);

        return response()->json(['sites' => $sites]);
    }

    public function create(): View
    {
        return view('sites.create', ['userGroups' => UserGroup::query()->orderBy('name')->pluck('name', 'id')]);
    }

    public function store(StoreSiteRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $validated['tenant_id'] = auth()->user()?->tenant_id;
        $validated['user_group_id'] = auth()->user()?->isOwner()
            ? ($validated['user_group_id'] ?? null)
            : auth()->user()?->user_group_id;

        $site = Site::query()->create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Site created successfully.',
                'site' => $site,
            ], 201);
        }

        return redirect()
            ->route('sites.show', $site)
            ->with('success', 'Site created successfully.');
    }

    public function show(Site $site): View
    {
        $this->authorizeTenantAccess($site);

        $site->load(['routers' => fn ($query) => $query->orderBy('name')])
            ->loadCount([
                'routers',
                'routers as online_routers_count' => fn ($query) => $query->where('status', 'online'),
                'routers as offline_routers_count' => fn ($query) => $query->where('status', 'offline'),
            ]);

        return view('sites.show', ['site' => $site]);
    }

    public function edit(Site $site): View
    {
        $this->authorizeTenantAccess($site);

        return view('sites.edit', [
            'site' => $site,
            'userGroups' => UserGroup::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdateSiteRequest $request, Site $site, UserGroupAssignmentService $groups): JsonResponse|RedirectResponse
    {
        $this->authorizeTenantAccess($site);

        $validated = $request->validated();
        $validated['user_group_id'] = auth()->user()?->isOwner()
            ? (isset($validated['user_group_id']) ? (int) $validated['user_group_id'] : null)
            : $site->user_group_id;

        if ($site->user_group_id !== $validated['user_group_id']) {
            $groups->cascadeSite($site->id, (string) $site->tenant_id, $validated['user_group_id']);
        }

        $site->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Site updated successfully.',
                'site' => $site->fresh(),
            ]);
        }

        return redirect()
            ->route('sites.show', $site)
            ->with('success', 'Site updated successfully.');
    }

    public function destroy(Request $request, Site $site): JsonResponse|RedirectResponse
    {
        $this->authorizeTenantAccess($site);

        $site->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Site deleted successfully.']);
        }

        return redirect()
            ->route('sites.index')
            ->with('success', 'Site deleted successfully.');
    }

    protected function authorizeTenantAccess(Site $site): void
    {
        if (auth()->check() && auth()->user()->tenant_id && $site->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'You do not have access to this site.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function siteRow(Site $site): array
    {
        return [
            'id' => $site->id,
            'code' => $site->code,
            'name' => $site->name,
            'description' => $site->description,
            'address' => $site->address,
            'latitude' => (float) $site->latitude,
            'longitude' => (float) $site->longitude,
            'status' => $site->status,
            'routers_count' => $site->routers_count ?? 0,
            'online_routers_count' => $site->online_routers_count ?? 0,
            'offline_routers_count' => $site->offline_routers_count ?? 0,
            'created_at' => $site->created_at?->format('M d, Y'),
        ];
    }

    private function siteHealth(Site $site): string
    {
        if (($site->routers_count ?? 0) === 0) {
            return 'empty';
        }

        if (($site->offline_routers_count ?? 0) > 0) {
            return 'degraded';
        }

        return 'online';
    }
}
