<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class TenantContext
{
    public function id(): ?string
    {
        if (app()->bound('current_tenant')) {
            return (string) app('current_tenant')->id;
        }
        foreach (['web', 'customer'] as $name) {
            $guard = Auth::guard($name);
            if ($guard->hasUser() && $guard->user()?->tenant_id) {
                return (string) $guard->user()->tenant_id;
            }
        }

        return null;
    }

    public function run(string $tenantId, callable $callback): mixed
    {
        $currentId = $this->id();
        abort_if($currentId !== null && $currentId !== $tenantId && ! Auth::guard('web')->user()?->isCentralAdmin(), 403);
        $previous = app()->bound('current_tenant') ? app('current_tenant') : null;
        $contract = \Stancl\Tenancy\Contracts\Tenant::class;
        $previousContract = app()->bound($contract) ? app($contract) : null;
        $tenant = Tenant::query()->findOrFail($tenantId);
        app()->instance('current_tenant', $tenant);
        app()->instance($contract, $tenant);
        try {
            return $callback();
        } finally {
            app()->forgetInstance('current_tenant');
            app()->forgetInstance($contract);
            if ($previous !== null) {
                app()->instance('current_tenant', $previous);
            }
            if ($previousContract !== null) {
                app()->instance($contract, $previousContract);
            }
        }
    }
}
