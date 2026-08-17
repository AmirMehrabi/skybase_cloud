<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserGroup\StoreUserGroupRequest;
use App\Http\Requests\UserGroup\UpdateUserGroupRequest;
use App\Models\UserGroup;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $groups = UserGroup::query()
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount(['users', 'customers', 'organizations', 'subscriptions', 'sites'])
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.tenant.user-groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.tenant.user-groups.create', ['userGroup' => new UserGroup]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserGroupRequest $request): RedirectResponse
    {
        $userGroup = UserGroup::create([
            ...$request->validated(),
            'tenant_id' => $this->currentTenantId($request),
        ]);

        return redirect()->route('admin.tenant.user-groups.show', $userGroup)
            ->with('success', 'User Group created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(UserGroup $userGroup): View
    {
        $this->authorizeTenantGroup($userGroup);
        $userGroup->loadCount(['users', 'customers', 'organizations', 'subscriptions', 'sites']);

        return view('admin.tenant.user-groups.show', compact('userGroup'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserGroup $userGroup): View
    {
        $this->authorizeTenantGroup($userGroup);

        return view('admin.tenant.user-groups.edit', compact('userGroup'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserGroupRequest $request, UserGroup $userGroup): RedirectResponse
    {
        $this->authorizeTenantGroup($userGroup);
        $userGroup->update($request->validated());

        return redirect()->route('admin.tenant.user-groups.show', $userGroup)
            ->with('success', 'User Group updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserGroup $userGroup): RedirectResponse
    {
        $this->authorizeTenantGroup($userGroup);

        $associations = collect($this->associationTables())
            ->mapWithKeys(fn (string $table, string $label): array => [
                $label => DB::table($table)
                    ->where('tenant_id', $userGroup->tenant_id)
                    ->where('user_group_id', $userGroup->id)
                    ->count(),
            ])
            ->filter();

        if ($associations->isNotEmpty()) {
            $summary = $associations->map(fn (int $count, string $label): string => "{$count} {$label}")->implode(', ');

            return back()->with('error', "This User Group cannot be deleted because it owns {$summary}.");
        }

        $userGroup->delete();

        return redirect()->route('admin.tenant.user-groups.index')
            ->with('success', 'User Group deleted successfully.');
    }

    private function authorizeTenantGroup(UserGroup $userGroup): void
    {
        abort_unless((string) $userGroup->tenant_id === $this->currentTenantId(request()), 403, PermissionRegistry::DENIED_MESSAGE);
    }

    private function currentTenantId(Request $request): string
    {
        $tenantId = tenant_id() ?? $request->user()?->tenant_id;

        abort_unless($tenantId, 403, PermissionRegistry::DENIED_MESSAGE);

        return (string) $tenantId;
    }

    /** @return array<string, string> */
    private function associationTables(): array
    {
        return [
            'users' => 'users',
            'organizations' => 'organizations',
            'customers' => 'customers',
            'subscriptions' => 'subscriptions',
            'sites' => 'sites',
            'routers' => 'routers',
            'access_points' => 'access points',
            'vpn_users' => 'VPN users',
            'invoices' => 'invoices',
            'tickets' => 'tickets',
            'work_orders' => 'work orders',
        ];
    }
}
