<?php

namespace App\Http\Controllers\Admin\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenant\StoreRoleRequest;
use App\Http\Requests\Admin\Tenant\UpdateRoleRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = $this->currentTenantId($request);
        Role::ensureDefaultsForTenant($tenantId);

        $roles = Role::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->each(function (Role $role) use ($tenantId): void {
                $role->setAttribute('users_count', $this->assignedUsersCount($tenantId, $role));
            });

        return view('admin.tenant.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.tenant.roles.create', [
            'role' => new Role(['permissions' => []]),
            'modules' => PermissionRegistry::modules(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'tenant_id' => $this->currentTenantId($request),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'permissions' => PermissionRegistry::sanitizePermissions($request->validated('permissions', [])),
        ]);

        return redirect()
            ->route('admin.tenant.roles.show', $role)
            ->with('success', 'نقش با موفقیت ایجاد شد.');
    }

    public function show(Request $request, Role $role): View
    {
        $this->authorizeTenantRole($request, $role);

        return view('admin.tenant.roles.show', [
            'role' => $role->setAttribute('users_count', $this->assignedUsersCount((string) $role->tenant_id, $role)),
            'modules' => PermissionRegistry::modules(),
        ]);
    }

    public function edit(Request $request, Role $role): View
    {
        $this->authorizeTenantRole($request, $role);

        return view('admin.tenant.roles.edit', [
            'role' => $role,
            'modules' => PermissionRegistry::modules(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorizeTenantRole($request, $role);
        $oldNormalizedName = $role->normalizedName();
        $oldRoleName = $role->name;

        $role->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'permissions' => PermissionRegistry::sanitizePermissions($request->validated('permissions', [])),
        ]);

        if ($oldNormalizedName !== $role->normalizedName()) {
            User::query()
                ->where('tenant_id', $role->tenant_id)
                ->whereIn('role', [$oldRoleName, $oldNormalizedName, strtolower($oldRoleName)])
                ->update(['role' => $role->name]);
        }

        return redirect()
            ->route('admin.tenant.roles.show', $role)
            ->with('success', 'نقش با موفقیت به روز شد.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeTenantRole($request, $role);

        if ($this->assignedUsersCount($this->currentTenantId($request), $role) > 0) {
            return back()->with('error', 'این نقش به کاربر اختصاص داده شده و قابل حذف نیست.');
        }

        if ($role->normalizedName() === 'owner') {
            return back()->with('error', 'نقش مالک قابل حذف نیست.');
        }

        $role->delete();

        return redirect()
            ->route('admin.tenant.roles.index')
            ->with('success', 'نقش با موفقیت حذف شد.');
    }

    private function authorizeTenantRole(Request $request, Role $role): void
    {
        abort_unless((string) $role->tenant_id === $this->currentTenantId($request), 403, PermissionRegistry::DENIED_MESSAGE);
    }

    private function currentTenantId(Request $request): string
    {
        $tenantId = tenant_id() ?? $request->user()?->tenant_id;

        if (! $tenantId) {
            abort(403, PermissionRegistry::DENIED_MESSAGE);
        }

        return (string) $tenantId;
    }

    private function assignedUsersCount(string $tenantId, Role $role): int
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('role', [$role->name, $role->normalizedName(), strtolower($role->name)])
            ->count();
    }
}
