<?php

namespace App\Services;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;

class CentralIdentityProvider extends EloquentUserProvider
{
    /** Authentication resolves identities before tenant middleware; application queries remain tenant-scoped. */
    protected function newModelQuery($model = null): Builder
    {
        return ($model ?? $this->createModel())->newQueryWithoutScopes();
    }
}
