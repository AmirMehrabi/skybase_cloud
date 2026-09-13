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

        if ($this->usesSiteMembership($model->getTable())) {
            $this->applySiteMembershipScope($builder, $model, $context->tenantId(), $context->groupId());

            return;
        }

        if ($context->groupId() === null) {
            $builder->whereNull($column);

            return;
        }

        $builder->where($column, $context->groupId());
    }

    private function usesSiteMembership(string $table): bool
    {
        return in_array($table, [
            'sites',
            'routers',
            'access_points',
            'ip_pools',
            'ip_addresses',
            'router_monitoring_states',
            'netflow_flows',
            'network_alerts',
            'network_bandwidth_samples',
            'network_usage_records',
        ], true);
    }

    private function applySiteMembershipScope(Builder $builder, Model $model, string $tenantId, ?int $groupId): void
    {
        $builder->where(function (Builder $query) use ($model, $tenantId, $groupId): void {
            $this->applyDirectGroupCondition($query, $model, $groupId);

            if ($groupId === null) {
                return;
            }

            match ($model->getTable()) {
                'sites' => $this->orWhereSitePivotMembership($query, $model, $tenantId, $groupId, 'id'),
                'routers' => $this->orWhereSitePivotMembership($query, $model, $tenantId, $groupId, 'site_id'),
                'access_points' => $this->applyAccessPointSiteMembership($query, $model, $tenantId, $groupId),
                'ip_pools' => $this->applyIpPoolSiteMembership($query, $model, $tenantId, $groupId),
                'ip_addresses' => $this->applyIpAddressSiteMembership($query, $model, $tenantId, $groupId),
                default => $this->orWhereRouterSiteMembership($query, $model, $tenantId, $groupId, 'router_id'),
            };
        });
    }

    private function applyDirectGroupCondition(Builder $query, Model $model, ?int $groupId): void
    {
        $groupColumn = $model->qualifyColumn('user_group_id');

        if ($groupId === null) {
            $query->whereNull($groupColumn);

            return;
        }

        $query->where($groupColumn, $groupId);
    }

    private function applyAccessPointSiteMembership(Builder $query, Model $model, string $tenantId, int $groupId): void
    {
        $this->orWhereSitePivotMembership($query, $model, $tenantId, $groupId, 'site_id');
        $this->orWhereRouterSiteMembership($query, $model, $tenantId, $groupId, 'router_id');
    }

    private function applyIpPoolSiteMembership(Builder $query, Model $model, string $tenantId, int $groupId): void
    {
        $this->orWhereSitePivotMembership($query, $model, $tenantId, $groupId, 'site_id');
        $this->orWhereRouterSiteMembership($query, $model, $tenantId, $groupId, 'router_id');

        $query->orWhereExists(function ($query) use ($model, $tenantId, $groupId): void {
            $query->selectRaw('1')
                ->from('ip_pool_router')
                ->join('routers', 'routers.id', '=', 'ip_pool_router.router_id')
                ->join('site_user_group', 'site_user_group.site_id', '=', 'routers.site_id')
                ->whereColumn('ip_pool_router.ip_pool_id', $model->qualifyColumn('id'))
                ->where('ip_pool_router.tenant_id', $tenantId)
                ->where('routers.tenant_id', $tenantId)
                ->where('site_user_group.tenant_id', $tenantId)
                ->where('site_user_group.user_group_id', $groupId);
        });
    }

    private function applyIpAddressSiteMembership(Builder $query, Model $model, string $tenantId, int $groupId): void
    {
        $query->orWhereExists(function ($query) use ($model, $tenantId, $groupId): void {
            $query->selectRaw('1')
                ->from('ip_pools')
                ->join('site_user_group', 'site_user_group.site_id', '=', 'ip_pools.site_id')
                ->whereColumn('ip_pools.id', $model->qualifyColumn('ip_pool_id'))
                ->where('ip_pools.tenant_id', $tenantId)
                ->where('site_user_group.tenant_id', $tenantId)
                ->where('site_user_group.user_group_id', $groupId);
        });

        $query->orWhereExists(function ($query) use ($model, $tenantId, $groupId): void {
            $query->selectRaw('1')
                ->from('ip_pools')
                ->join('routers', 'routers.id', '=', 'ip_pools.router_id')
                ->join('site_user_group', 'site_user_group.site_id', '=', 'routers.site_id')
                ->whereColumn('ip_pools.id', $model->qualifyColumn('ip_pool_id'))
                ->where('ip_pools.tenant_id', $tenantId)
                ->where('routers.tenant_id', $tenantId)
                ->where('site_user_group.tenant_id', $tenantId)
                ->where('site_user_group.user_group_id', $groupId);
        });

        $query->orWhereExists(function ($query) use ($model, $tenantId, $groupId): void {
            $query->selectRaw('1')
                ->from('ip_pool_router')
                ->join('routers', 'routers.id', '=', 'ip_pool_router.router_id')
                ->join('site_user_group', 'site_user_group.site_id', '=', 'routers.site_id')
                ->whereColumn('ip_pool_router.ip_pool_id', $model->qualifyColumn('ip_pool_id'))
                ->where('ip_pool_router.tenant_id', $tenantId)
                ->where('routers.tenant_id', $tenantId)
                ->where('site_user_group.tenant_id', $tenantId)
                ->where('site_user_group.user_group_id', $groupId);
        });
    }

    private function orWhereSitePivotMembership(
        Builder $query,
        Model $model,
        string $tenantId,
        int $groupId,
        string $siteColumn,
    ): void {
        $query->orWhereExists(function ($query) use ($model, $tenantId, $groupId, $siteColumn): void {
            $query->selectRaw('1')
                ->from('site_user_group')
                ->whereColumn('site_user_group.site_id', $model->qualifyColumn($siteColumn))
                ->where('site_user_group.tenant_id', $tenantId)
                ->where('site_user_group.user_group_id', $groupId);
        });

        $query->orWhereExists(function ($query) use ($model, $tenantId, $groupId, $siteColumn): void {
            $query->selectRaw('1')
                ->from('sites')
                ->whereColumn('sites.id', $model->qualifyColumn($siteColumn))
                ->where('sites.tenant_id', $tenantId)
                ->where('sites.user_group_id', $groupId);
        });
    }

    private function orWhereRouterSiteMembership(
        Builder $query,
        Model $model,
        string $tenantId,
        int $groupId,
        string $routerColumn,
    ): void {
        $query->orWhereExists(function ($query) use ($model, $tenantId, $groupId, $routerColumn): void {
            $query->selectRaw('1')
                ->from('routers')
                ->join('site_user_group', 'site_user_group.site_id', '=', 'routers.site_id')
                ->whereColumn('routers.id', $model->qualifyColumn($routerColumn))
                ->where('routers.tenant_id', $tenantId)
                ->where('site_user_group.tenant_id', $tenantId)
                ->where('site_user_group.user_group_id', $groupId);
        });

        $query->orWhereExists(function ($query) use ($model, $tenantId, $groupId, $routerColumn): void {
            $query->selectRaw('1')
                ->from('routers')
                ->join('sites', 'sites.id', '=', 'routers.site_id')
                ->whereColumn('routers.id', $model->qualifyColumn($routerColumn))
                ->where('routers.tenant_id', $tenantId)
                ->where('sites.tenant_id', $tenantId)
                ->where('sites.user_group_id', $groupId);
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
