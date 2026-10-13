<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));
        static::creating(function (Model $model): void {
            if (blank($model->getAttribute('tenant_id'))) {
                $model->setAttribute('tenant_id', app(TenantContext::class)->id());
            }
        });
    }

    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation): BelongsTo
    {
        if ($child->exists && filled($child->tenant_id) && isset($query->getModel()->getGlobalScopes()['tenant'])) {
            $query->where($query->qualifyColumn('tenant_id'), $child->tenant_id);
        }

        return parent::newBelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }

    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey): HasMany
    {
        if ($parent->exists && filled($parent->tenant_id) && isset($query->getModel()->getGlobalScopes()['tenant'])) {
            if (in_array($query->getModel()->getTable(), ['invoice_items', 'user_settings'], true)) {
                $query->withGlobalScope('tenant', function (Builder $relatedQuery) use ($parent): void {
                    TenantScope::constrain($relatedQuery, (string) $parent->tenant_id);
                    $currentId = app(TenantContext::class)->id();
                    if ($currentId !== null && $currentId !== (string) $parent->tenant_id) {
                        $relatedQuery->whereRaw('1 = 0');
                    }
                });
            } else {
                $query->where($query->qualifyColumn('tenant_id'), $parent->tenant_id);
            }
        } elseif ($parent->exists && $parent instanceof User && $parent->isCentralAdmin() && $query->getModel() instanceof UserSetting) {
            $query->withGlobalScope('tenant', function (Builder $relatedQuery) use ($parent): void {
                $relatedQuery->where('user_id', $parent->id)->whereNull('tenant_id');
                if (app(TenantContext::class)->id() !== null) {
                    $relatedQuery->whereRaw('1 = 0');
                }
            });
        }

        return parent::newHasMany($query, $parent, $foreignKey, $localKey);
    }
}
