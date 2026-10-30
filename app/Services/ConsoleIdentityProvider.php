<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class ConsoleIdentityProvider extends CentralIdentityProvider
{
    protected function newModelQuery($model = null): Builder
    {
        return parent::newModelQuery($model)->whereNull('tenant_id')->where('role', 'super_admin')->where('status', 'active');
    }
}
