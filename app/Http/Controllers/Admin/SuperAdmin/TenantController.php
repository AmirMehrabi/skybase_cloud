<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\BulkDeleteTenantRequest;
use App\Http\Requests\Owner\DeleteTenantRequest;
use App\Http\Requests\Owner\ManageTenantRequest;
use App\Models\SkybaseAccount;
use App\Models\SkybaseInvoice;
use App\Models\Tenant;
use App\Models\TenantAdminEvent;
use App\Models\TenantNote;
use App\Services\SkybaseBillingService;
use App\Services\SkybasePackageCatalog;
use App\Services\TenantContext;
use App\Services\TenantOperationalOverview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-owner-console');
        $query = Tenant::query()->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.substr($request->string('search'), 0, 100).'%')->orWhere('company_name', 'like', '%'.substr($request->string('search'), 0, 100).'%')))
            ->when($request->input('status') === 'suspended', fn ($q) => $q->where(fn ($q) => $q->where('status', 'suspended')->orWhere('application_access_suspended', true)))
            ->when(in_array($request->input('status'), ['active', 'pending'], true), fn ($q) => $q->where('status', $request->input('status'))->where('application_access_suspended', false));
        if ($request->input('archived') === 'yes') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }
        if ($request->filled('commercial') || $request->filled('activity')) {
            $matchingIds = [];
            (clone $query)->chunkById(100, function ($candidates) use ($request, &$matchingIds): void {
                foreach ($candidates as $candidate) {
                    $matches = app(TenantContext::class)->run($candidate->id, function () use ($candidate, $request): bool {
                        $account = SkybaseAccount::query()->where('tenant_id', $candidate->id)->first();
                        $commercial = $request->input('commercial');
                        $commercialMatch = ! $commercial || match ($commercial) {
                            'unconfigured' => ! $account || $account->status === 'unconfigured',
                            'expiring_trial' => $account?->trial_ends_at?->isFuture() && $account->trial_ends_at->lte(now()->addDays(7)),
                            'expired' => $account?->paid_through?->isPast() === true,
                            default => $account?->status === $commercial,
                        };
                        $lastLogin = $candidate->users()->max('last_login_at');
                        $activityMatch = $request->input('activity') !== 'inactive' || ! $lastLogin || Carbon::parse($lastLogin)->lt(now()->subDays(30));

                        return $commercialMatch && $activityMatch;
                    });
                    if ($matches) {
                        $matchingIds[] = $candidate->id;
                    }
                }
            });
            $query->whereIn('id', $matchingIds);
        }
        $totals = ['active' => Tenant::query()->whereNull('archived_at')->where('status', 'active')->where('application_access_suspended', false)->count(), 'suspended' => Tenant::query()->whereNull('archived_at')->where(fn ($q) => $q->where('status', 'suspended')->orWhere('application_access_suspended', true))->count(), 'archived' => Tenant::query()->whereNotNull('archived_at')->count()];
        $tenants = $query->latest()->paginate(20)->withQueryString();
        $summaries = [];
        foreach ($tenants as $tenant) {
            $summaries[$tenant->id] = app(TenantContext::class)->run($tenant->id, fn (): array => ['account' => SkybaseAccount::query()->where('tenant_id', $tenant->id)->first()] + app(TenantOperationalOverview::class)->forTenant($tenant));
        }

        return view('modules.owner.index', compact('tenants', 'summaries', 'totals'));
    }

    public function show(Tenant $tenant): View
    {
        Gate::authorize('view-owner-console');
        $data = app(TenantContext::class)->run($tenant->id, fn (): array => [
            'account' => SkybaseAccount::query()->where('tenant_id', $tenant->id)->first(),
            'staff' => $tenant->users()->latest()->limit(20)->get(['id', 'name', 'email', 'role', 'last_login_at']),
            'overview' => app(TenantOperationalOverview::class)->forTenant($tenant, true),
            'notes' => TenantNote::query()->where('tenant_id', $tenant->id)->latest()->limit(30)->get(),
            'events' => TenantAdminEvent::query()->where('tenant_id', $tenant->id)->latest()->limit(50)->get(),
            'invoices' => SkybaseInvoice::query()->where('tenant_id', $tenant->id)->latest()->limit(20)->get(),
        ]);

        return view('modules.owner.show', $data + ['tenant' => $tenant, 'packages' => app(SkybasePackageCatalog::class)->all()]);
    }

    public function destroy(DeleteTenantRequest $request, Tenant $tenant, SkybaseBillingService $billing): RedirectResponse
    {
        Gate::authorize('delete-owner-tenants');
        app(TenantContext::class)->run($tenant->id, function () use ($tenant, $billing): void {
            $tenant->getConnection()->transaction(function () use ($tenant, $billing): void {
                $lockedTenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
                $this->deleteTenant($lockedTenant, $billing);
            });
        });

        return redirect()->route('admin.super-admin.tenants.index')->with('success', 'Tenant deleted. Access is blocked and records are retained for recovery.');
    }

    public function bulkDestroy(BulkDeleteTenantRequest $request, SkybaseBillingService $billing): RedirectResponse
    {
        Gate::authorize('delete-owner-tenants');
        $tenantIds = $request->validated('tenant_ids');
        $tenants = Tenant::query()->whereIn('id', $tenantIds)->orderBy('id')->get();
        if ($tenants->count() !== count($tenantIds)) {
            throw ValidationException::withMessages(['tenant_ids' => 'One or more selected tenants are no longer available. Refresh the list and try again.']);
        }

        $tenants->first()->getConnection()->transaction(function () use ($tenantIds, $billing): void {
            $lockedTenants = Tenant::query()->whereIn('id', $tenantIds)->orderBy('id')->lockForUpdate()->get();
            if ($lockedTenants->count() !== count($tenantIds)) {
                throw ValidationException::withMessages(['tenant_ids' => 'One or more selected tenants are no longer available. Refresh the list and try again.']);
            }

            foreach ($lockedTenants as $tenant) {
                app(TenantContext::class)->run($tenant->id, function () use ($tenant, $billing): void {
                    $this->deleteTenant($tenant, $billing);
                });
            }
        });

        return redirect()->route('admin.super-admin.tenants.index')->with('success', count($tenantIds).' tenants deleted. Access is blocked and records are retained for recovery.');
    }

    private function deleteTenant(Tenant $tenant, SkybaseBillingService $billing): void
    {
        $billing->event($tenant->id, 'owner.deleted', 'Deletion confirmed from console', ['name' => $tenant->name]);
        $tenant->update(['application_access_suspended' => true]);
        $tenant->delete();
    }

    public function edit(Tenant $tenant): View
    {
        return $this->show($tenant);
    }

    public function update(ManageTenantRequest $request, Tenant $tenant, SkybaseBillingService $billing): RedirectResponse
    {
        Gate::authorize($request->input('action') === 'suspend' || $request->input('action') === 'archive' ? 'suspend-owner-tenants' : 'manage-owner-commercial');
        $data = $request->validated();
        app(TenantContext::class)->run($tenant->id, function () use ($tenant, $data, $billing): void {
            $tenant->getConnection()->transaction(function () use ($tenant, $data, $billing): void {
                $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
                $before = $tenant->only(['name', 'company_name', 'email', 'phone', 'status', 'application_access_suspended', 'archived_at']);
                $account = $billing->account($tenant->id);
                $before['commercial'] = $account->toArray();
                switch ($data['action']) {
                    case 'contacts':
                        $tenant->update(array_intersect_key($data, array_flip(['name', 'company_name', 'email', 'phone'])));
                        break;
                    case 'package':
                        $account->update($billing->terms($data['package_key']) + ['status' => 'active']);
                        SkybaseInvoice::query()->where('tenant_id', $tenant->id)->where('status', 'pending')->update(['status' => 'void']);
                        break;
                    case 'trial':
                        $account->update(['trial_ends_at' => $data['trial_ends_at'], 'status' => 'trial']);
                        break;
                    case 'limit':
                        $account->update(['subscriber_limit' => $data['subscriber_limit']]);
                        break;
                    case 'note':
                        TenantNote::query()->create(['tenant_id' => $tenant->id, 'actor_id' => auth()->id(), 'body' => $data['reason']]);
                        break;
                    case 'suspend':
                        $tenant->update(['application_access_suspended' => true]);
                        break;
                    case 'activate':
                        $tenant->update(['status' => 'active', 'application_access_suspended' => false, 'archived_at' => null]);
                        break;
                    case 'archive':
                        $tenant->update(['application_access_suspended' => true, 'archived_at' => now()]);
                        break;
                }
                $billing->event($tenant->id, 'owner.'.$data['action'], $data['reason'], ['before' => $before, 'after' => ['tenant' => $tenant->only(['name', 'company_name', 'email', 'phone', 'status', 'application_access_suspended', 'archived_at']), 'commercial' => $account->fresh()->toArray()]]);
            });
        });

        return redirect()->route('admin.super-admin.tenants.show', $tenant)->with('success', 'Account updated.');
    }
}
