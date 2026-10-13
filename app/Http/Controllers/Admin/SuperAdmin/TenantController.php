<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SuperAdmin\UpdateTenantRequest;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\SecurityEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-central-tenants');
        $tenants = Tenant::query()
            ->latest()
            ->paginate(20);

        foreach ($tenants as $tenant) {
            $tenant->load(['plan', 'users'])->loadCount(['users', 'customers', 'routers']);
        }

        return view('admin.super-admin.tenants.index', compact('tenants'));
    }

    public function show(Tenant $tenant): View
    {
        Gate::authorize('manage-central-tenants');
        $tenant->load(['plan', 'users', 'settings', 'roles']);
        $userCount = $tenant->users()->count();
        $customerCount = $tenant->customers()->count();
        $routerCount = $tenant->routers()->count();

        return view('admin.super-admin.tenants.show', compact('tenant', 'userCount', 'customerCount', 'routerCount'));
    }

    public function edit(Tenant $tenant): View
    {
        Gate::authorize('manage-central-tenants');
        $plans = Plan::query()->where('tenant_id', $tenant->id)->active()->ordered()->get();

        return view('admin.super-admin.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        Gate::authorize('manage-central-tenants');
        $validated = $request->validated();

        $tenant->update($validated);
        app(SecurityEventLogger::class)->record('central.tenant.updated', ['user_id' => auth('web')->id(), 'tenant_id' => $tenant->id]);

        return redirect()
            ->route('admin.super-admin.tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        Gate::authorize('manage-central-tenants');
        $tenant->update(['status' => 'suspended']);
        app(SecurityEventLogger::class)->record('central.tenant.suspended', ['user_id' => auth('web')->id(), 'tenant_id' => $tenant->id]);

        return redirect()
            ->route('admin.super-admin.tenants.show', $tenant)
            ->with('success', 'Tenant has been suspended.');
    }

    public function activate(Tenant $tenant): RedirectResponse
    {
        Gate::authorize('manage-central-tenants');
        $tenant->update(['status' => 'active']);
        app(SecurityEventLogger::class)->record('central.tenant.activated', ['user_id' => auth('web')->id(), 'tenant_id' => $tenant->id]);

        return redirect()
            ->route('admin.super-admin.tenants.show', $tenant)
            ->with('success', 'Tenant has been activated.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        Gate::authorize('manage-central-tenants');
        app(SecurityEventLogger::class)->record('central.tenant.deleted', ['user_id' => auth('web')->id(), 'tenant_id' => $tenant->id]);
        $tenant->delete();

        return redirect()
            ->route('admin.super-admin.tenants.index')
            ->with('success', 'Tenant has been deleted.');
    }
}
