<?php

namespace App\Http\Controllers;

use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\UserGroup;
use App\Services\ActivityLogFormatter;
use App\Services\OrganizationBillingService;
use App\Services\UserGroupAssignmentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        return view('organizations.index');
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'billing']);

        $organizations = Organization::query()
            ->filter($filters)
            ->with('defaultPlan')
            ->withCount('customers')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 100))
            ->through(fn (Organization $organization) => [
                'id' => $organization->id,
                'code' => $organization->code,
                'name' => $organization->name,
                'status' => $organization->status,
                'billing_enabled' => (bool) $organization->billing_enabled,
                'default_plan' => $organization->defaultPlan?->name ?? 'Not set',
                'default_billing_cycle' => $organization->default_billing_cycle ?? 'N/A',
                'customers_count' => $organization->customers_count,
                'created_at' => $organization->created_at?->format('M d, Y'),
            ]);

        return response()->json([
            'organizations' => $organizations->items(),
            'pagination' => [
                'current_page' => $organizations->currentPage(),
                'last_page' => $organizations->lastPage(),
                'per_page' => $organizations->perPage(),
                'total' => $organizations->total(),
                'from' => $organizations->firstItem(),
                'to' => $organizations->lastItem(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(Organization::getStats());
    }

    public function create(): View
    {
        return view('organizations.create', [
            'plans' => $this->activePlans(),
            'userGroups' => UserGroup::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(StoreOrganizationRequest $request, OrganizationBillingService $billing): RedirectResponse
    {
        $validated = $this->normalizedPayload($request->validated());

        $organization = Organization::query()->create($validated);
        $billing->syncOrganizationSubscriptions($organization);

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization created successfully.');
    }

    public function show(Organization $organization): View
    {
        $organization->load([
            'defaultPlan',
            'customers' => fn ($query) => $query->latest()->limit(20),
            'customers.subscriptions.plan',
        ])->loadCount('customers');

        $activityLog = app(ActivityLogFormatter::class)->forSubject($organization, $organization->tenant_id);

        return view('organizations.show', [
            'organization' => $organization,
            'activityLog' => $activityLog,
        ]);
    }

    public function edit(Organization $organization): View
    {
        return view('organizations.edit', [
            'organization' => $organization,
            'plans' => $this->activePlans(),
            'userGroups' => UserGroup::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization, OrganizationBillingService $billing, UserGroupAssignmentService $groups): RedirectResponse
    {
        $organization->update($this->normalizedPayload($request->validated(), $organization));

        if ($organization->wasChanged('user_group_id')) {
            $groups->cascadeOrganization($organization->id, (string) $organization->tenant_id, $organization->user_group_id);
        }

        $billing->syncOrganizationSubscriptions($organization->fresh('defaultPlan'));

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }

    public function destroy(Request $request, Organization $organization): JsonResponse|RedirectResponse
    {
        if ($organization->customers()->exists()) {
            $message = 'Cannot delete an organization while customers are assigned to it.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $organization->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Organization deleted successfully.']);
        }

        return redirect()
            ->route('organizations.index')
            ->with('success', 'Organization deleted successfully.');
    }

    /**
     * @return Collection<int, Plan>
     */
    protected function activePlans()
    {
        return Plan::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'price', 'billing_cycle']);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function normalizedPayload(array $validated, ?Organization $organization = null): array
    {
        $billingEnabled = (bool) ($validated['billing_enabled'] ?? false);
        $validated['user_group_id'] = auth()->user()?->isOwner()
            ? ($validated['user_group_id'] ?? null)
            : ($organization?->user_group_id ?? auth()->user()?->user_group_id);

        return [
            ...$validated,
            'tenant_id' => $organization?->tenant_id ?? auth()->user()?->tenant_id,
            'billing_disabled_at' => $billingEnabled ? null : ($organization?->billing_disabled_at ?? now()),
        ];
    }
}
