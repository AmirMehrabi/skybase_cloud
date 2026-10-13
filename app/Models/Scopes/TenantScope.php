<?php

namespace App\Models\Scopes;

use App\Models\Invoice;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

class TenantScope
{
    public static function apply(Builder $query): void
    {
        $tenantId = app(TenantContext::class)->id();
        if ($tenantId !== null) {
            self::constrain($query, $tenantId);

            return;
        }
        $wheres = $query->getQuery()->wheres ?? [];
        if (collect($wheres)->contains(fn (array $where): bool => ($where['boolean'] ?? 'and') === 'or')) {
            $query->whereRaw('1 = 0');

            return;
        }
        foreach ($wheres as $where) {
            if (($where['type'] ?? '') === 'Basic'
                && in_array($where['column'] ?? '', ['tenant_id', $query->qualifyColumn('tenant_id')], true)
                && ($where['operator'] ?? '') === '='
                && filled($where['value'] ?? null)
                && ($where['boolean'] ?? 'and') === 'and') {
                return;
            }
        }
        $query->whereRaw('1 = 0');
    }

    public static function constrain(Builder $query, string $tenantId): void
    {
        $parent = match ($query->getModel()->getTable()) {
            'invoice_items' => [Invoice::class, 'invoice_id'],
            'user_settings' => [User::class, 'user_id'],
            default => null,
        };
        if ($parent === null) {
            $query->where($query->qualifyColumn('tenant_id'), $tenantId);

            return;
        }
        [$model, $foreignKey] = $parent;
        $parentQuery = $model::withoutGlobalScopes()->select('id')->where('tenant_id', $tenantId)
            ->whereColumn((new $model)->getTable().'.id', $query->qualifyColumn($foreignKey));
        $query->where(function (Builder $tenantQuery) use ($query, $tenantId, $parentQuery): void {
            $tenantQuery->where($query->qualifyColumn('tenant_id'), $tenantId)
                ->orWhere(fn (Builder $legacyQuery): Builder => $legacyQuery->whereNull($query->qualifyColumn('tenant_id'))->whereExists($parentQuery->getQuery()));
        });
    }
}
