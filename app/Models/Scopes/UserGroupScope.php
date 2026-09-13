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

        $organizationPivot = match ($model->getTable()) {
            'customers' => ['customer_organization', 'customer_id'],
            'subscriptions' => ['organization_subscription', 'subscription_id'],
            default => null,
        };

        if ($organizationPivot !== null) {
            $this->applyOrganizationMembershipScope(
                $builder,
                $model,
                $context->tenantId(),
                $context->groupId(),
                $organizationPivot[0],
                $organizationPivot[1],
            );

            return;
        }

        if ($model->getTable() === 'routers') {
            $this->applyRouterSiteScope($builder, $model, $context->tenantId(), $context->groupId());

            return;
        }

        if ($context->groupId() === null) {
            $builder->whereNull($column);

            return;
        }

        $builder->where($column, $context->groupId());
    }

    private function applyRouterSiteScope(Builder $builder, Model $model, string $tenantId, ?int $groupId): void
    {
        $builder->where(function (Builder $query) use ($model, $tenantId, $groupId): void {
            $groupColumn = $model->qualifyColumn('user_group_id');

            if ($groupId === null) {
                $query->whereNull($groupColumn);
            } else {
                $query->where($groupColumn, $groupId);
            }

            $query->orWhereExists(function ($query) use ($model, $tenantId, $groupId): void {
                $query->selectRaw('1')
                    ->from('sites')
                    ->whereColumn('sites.id', $model->qualifyColumn('site_id'))
                    ->where('sites.tenant_id', $tenantId)
                    ->when(
                        $groupId === null,
                        fn ($query) => $query->whereNull('sites.user_group_id'),
                        fn ($query) => $query->where('sites.user_group_id', $groupId),
                    );
            });
        });
    }

    private function applyOrganizationMembershipScope(
        Builder $builder,
        Model $model,
        string $tenantId,
        ?int $groupId,
        string $pivotTable,
        string $pivotParentColumn,
    ): void {
        $builder->where(function (Builder $query) use ($model, $tenantId, $groupId, $pivotTable, $pivotParentColumn): void {
            $groupColumn = $model->qualifyColumn('user_group_id');

            if ($groupId === null) {
                $query->whereNull($groupColumn);
            } else {
                $query->where($groupColumn, $groupId);
            }

            $query->orWhereExists(function ($query) use ($model, $tenantId, $groupId, $pivotTable, $pivotParentColumn): void {
                $query->selectRaw('1')
                    ->from($pivotTable)
                    ->join('organizations', 'organizations.id', '=', $pivotTable.'.organization_id')
                    ->whereColumn($pivotTable.'.'.$pivotParentColumn, $model->qualifyColumn('id'))
                    ->where($pivotTable.'.tenant_id', $tenantId)
                    ->where('organizations.tenant_id', $tenantId)
                    ->whereNull('organizations.deleted_at')
                    ->when(
                        $groupId === null,
                        fn ($query) => $query->whereNull('organizations.user_group_id'),
                        fn ($query) => $query->where('organizations.user_group_id', $groupId),
                    );
            });
        });
    }
}
