<?php

use App\Models\Tenant;

if (! function_exists('tenant')) {
    /**
     * Get the current tenant.
     */
    function tenant(): ?Tenant
    {
        if (! app()->bound('current_tenant')) {
            return null;
        }

        return app('current_tenant');
    }
}

if (! function_exists('tenant_id')) {
    /**
     * Get the current tenant ID.
     */
    function tenant_id(): ?string
    {
        return tenant()?->id;
    }
}
private function makeNtPasswordHash(string $password): string
{
    return strtoupper(hash(
        'md4',
        mb_convert_encoding($password, 'UTF-16LE', 'UTF-8')
    ));
}