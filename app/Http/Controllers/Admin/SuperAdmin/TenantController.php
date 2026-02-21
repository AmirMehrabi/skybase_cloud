<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::with('plan', 'users')
            ->withCount('users', 'customers', 'routers')
            ->latest()
            ->paginate(20);

        return view('admin.super-admin.tenants.index', compact('tenants'));
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load(['plan', 'users', 'settings', 'roles']);
        $userCount = $tenant->users()->count();
        $customerCount = $tenant->customers()->count();
        $routerCount = $tenant->routers()->count();

        return view('admin.super-admin.tenants.show', compact('tenant', 'userCount', 'customerCount', 'routerCount'));
    }

    public function edit(Tenant $tenant): View
    {
        $plans = \App\Models\Plan::active()->ordered()->get();

        return view('admin.super-admin.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:tenants,email,'.$tenant->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:pending,active,suspended'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'trial_ends_at' => ['nullable', 'date'],
        ]);

        $tenant->update($validated);

        return redirect()
            ->route('admin.super-admin.tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'suspended']);

        return redirect()
            ->route('admin.super-admin.tenants.show', $tenant)
            ->with('success', 'Tenant has been suspended.');
    }

    public function activate(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'active']);

        return redirect()
            ->route('admin.super-admin.tenants.show', $tenant)
            ->with('success', 'Tenant has been activated.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        return redirect()
            ->route('admin.super-admin.tenants.index')
            ->with('success', 'Tenant has been deleted.');
    }
}
