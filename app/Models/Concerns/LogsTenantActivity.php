<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsTenantActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(str($this->getTable())->singular()->value())
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept($this->activityLogExcept())
            ->setDescriptionForEvent(
                fn (string $eventName): string => class_basename($this).' '.$eventName
            );
    }

    public function beforeActivityLogged(Model $activity, string $eventName): void
    {
        $tenantId = $this->getAttribute('tenant_id')
            ?? data_get($activity->causer, 'tenant_id')
            ?? tenant_id()
            ?? auth()->user()?->tenant_id;

        if ($tenantId !== null) {
            $activity->tenant_id = (string) $tenantId;
        }
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcept(): array
    {
        return ['updated_at', 'deleted_at'];
    }
}
