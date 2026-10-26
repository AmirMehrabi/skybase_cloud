<?php

namespace App\Support;

use App\Models\Setting;

class CustomerPortalSettings
{
    public static function showsBilling(string $tenantId): bool
    {
        return (bool) Setting::get('customer_portal.show_billing', true, $tenantId);
    }
}
