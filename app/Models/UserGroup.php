<?php

namespace App\Models;

use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\UserGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGroup extends Model
{
    /** @use HasFactory<UserGroupFactory> */
    use HasFactory, LogsTenantActivity;

    protected $fillable = ['tenant_id', 'name', 'description'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            $tenantId = tenant_id() ?? auth()->user()?->tenant_id;

            if ($tenantId) {
                $query->where($query->qualifyColumn('tenant_id'), $tenantId);
            }
        });

        static::creating(function (UserGroup $userGroup): void {
            if (blank($userGroup->tenant_id)) {
                $userGroup->tenant_id = tenant_id() ?? auth()->user()?->tenant_id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }
}
