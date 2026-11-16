<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SbukSyncRun extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'triggered_by', 'plan_id', 'router_id', 'ip_pool_id', 'status',
        'total_records', 'valid_records', 'processed_records', 'created_customers', 'matched_customers',
        'created_subscriptions', 'skipped_subscriptions', 'failed_records', 'error_summary',
        'personnel_snapshot', 'preview_summary', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'personnel_snapshot' => 'encrypted',
            'preview_summary' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function records(): HasMany
    {
        return $this->hasMany(SbukSyncRunRecord::class)->where('tenant_id', $this->tenant_id);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));
    }
}
