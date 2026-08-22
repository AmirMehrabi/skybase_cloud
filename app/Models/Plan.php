<?php

namespace App\Models;

use App\Models\Concerns\LogsTenantActivity;
use App\Services\RadiusProvisioningService;
use App\Services\TrafficShaping\PlanTrafficShapingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory, LogsTenantActivity;

    protected $fillable = [
        'name',
        'tenant_id',
        'internal_name',
        'description',
        'status',
        'visibility',
        'type',
        'category',
        'download_speed',
        'upload_speed',
        'burst_download',
        'burst_upload',
        'bandwidth_unit',
        'shaping_mode',
        'burst_threshold_download',
        'burst_threshold_upload',
        'burst_time_download',
        'burst_time_upload',
        'min_download_speed',
        'min_upload_speed',
        'shaping_priority',
        'queue_type',
        'data_limit',
        'data_unit',
        'data_cap_action',
        'throttle_download_speed',
        'throttle_upload_speed',
        'unlimited',
        'price',
        'currency',
        'billing_cycle',
        'grace_period_days',
        'setup_fee',
        'tax_profile',
        'router_profile',
        'ip_pool',
        'priority',
        'contract_required',
        'contract_duration',
        'available_from',
        'available_to',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unlimited' => 'boolean',
            'contract_required' => 'boolean',
            'price' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'grace_period_days' => 'integer',
            'burst_threshold_download' => 'integer',
            'burst_threshold_upload' => 'integer',
            'burst_time_download' => 'integer',
            'burst_time_upload' => 'integer',
            'min_download_speed' => 'integer',
            'min_upload_speed' => 'integer',
            'shaping_priority' => 'integer',
            'throttle_download_speed' => 'integer',
            'throttle_upload_speed' => 'integer',
            'available_from' => 'date',
            'available_to' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('priority')->orderBy('name');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('internal_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['type'] ?? null, function ($query, $type) {
            $query->where('type', $type);
        })->when($filters['category'] ?? null, function ($query, $category) {
            $query->where('category', $category);
        })->when($filters['billing_cycle'] ?? null, function ($query, $billingCycle) {
            $query->where('billing_cycle', $billingCycle);
        });
    }

    public function getSubscribersCountAttribute(): int
    {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->where('plan_id', $this->id)
            ->where('status', 'active')
            ->count();
    }

    public function usesAdvancedShaping(): bool
    {
        return $this->shaping_mode === 'advanced';
    }

    public function mikrotikRateLimit(): ?string
    {
        return app(PlanTrafficShapingService::class)->mikrotikRateLimit($this);
    }

    public function trafficShapingSummary(): string
    {
        return app(PlanTrafficShapingService::class)->summary($this);
    }

    public function effectiveDownloadSpeed(): int
    {
        return (int) $this->download_speed;
    }

    public function effectiveUploadSpeed(): int
    {
        return (int) $this->upload_speed;
    }

    public function shouldThrottleAfterDataLimit(): bool
    {
        return $this->data_cap_action === 'throttle';
    }

    public function dataLimitBytes(): ?int
    {
        if ($this->unlimited || ! $this->data_limit) {
            return null;
        }

        return match ($this->data_unit) {
            'MB' => (int) $this->data_limit * 1048576,
            'TB' => (int) $this->data_limit * 1099511627776,
            default => (int) $this->data_limit * 1073741824,
        };
    }

    public function hasFiniteDataAllowance(): bool
    {
        return ($this->dataLimitBytes() ?? 0) > 0;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if ($tenantId = tenant_id() ?? auth()->user()?->tenant_id) {
                $query->where('tenant_id', $tenantId);
            }
        });

        static::creating(function (Plan $plan): void {
            $plan->tenant_id ??= tenant_id() ?? auth()->user()?->tenant_id;
        });

        static::saved(function (Plan $plan): void {
            if (! $plan->wasRecentlyCreated && ! $plan->wasChanged([
                'status',
                'download_speed',
                'upload_speed',
                'burst_download',
                'burst_upload',
                'bandwidth_unit',
                'shaping_mode',
                'burst_threshold_download',
                'burst_threshold_upload',
                'burst_time_download',
                'burst_time_upload',
                'min_download_speed',
                'min_upload_speed',
                'shaping_priority',
                'internal_name',
                'router_profile', 'billing_cycle', 'grace_period_days', 'data_limit', 'data_unit', 'unlimited', 'data_cap_action',
            ])) {
                return;
            }

            app(RadiusProvisioningService::class)->syncSubscriptionsForPlan($plan);

            $plan->subscriptions()->each(function (Subscription $subscription) use ($plan): void {
                $subscription->update([
                    'billing_cycle' => $plan->billing_cycle,
                    'grace_period_days' => $plan->grace_period_days,
                ]);
                $subscription->usageCycles()->whereNull('closed_at')->update([
                    'plan_id' => $plan->id,
                    'allowance_bytes' => $plan->dataLimitBytes(),
                ]);
            });
        });
    }
}
