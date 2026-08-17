<?php

namespace App\Models\Scopes;

use App\Support\UserGroups\UserGroupContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserGroupScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(UserGroupContext::class);

        if ($context->tenantId() !== null) {
            $builder->where($model->qualifyColumn('tenant_id'), $context->tenantId());
        }

        if (! $context->shouldScope()) {
            return;
        }

        $column = $model->qualifyColumn('user_group_id');

        if ($context->groupId() === null) {
            $builder->whereNull($column);

            return;
        }

        $builder->where($column, $context->groupId());
    }
}
