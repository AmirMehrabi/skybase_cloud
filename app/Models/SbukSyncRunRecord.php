<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SbukSyncRunRecord extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'sbuk_sync_run_id', 'row_number', 'identity_hash', 'outcome', 'detail'];

    public function run(): BelongsTo
    {
        return $this->belongsTo(SbukSyncRun::class, 'sbuk_sync_run_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));
    }
}
