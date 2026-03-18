<?php

namespace App\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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
        'data_limit',
        'data_unit',
        'unlimited',
        'price',
        'currency',
        'billing_cycle',
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
            'available_from' => 'date',
            'available_to' => 'date',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
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
        return Customer::query()
            ->withoutGlobalScopes()
            ->where('plan', $this->name)
            ->count();
    }
}
