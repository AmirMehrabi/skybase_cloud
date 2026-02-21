<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'permissions',
        'description',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public static function getDefaultRoles(): array
    {
        return [
            [
                'name' => 'Owner',
                'permissions' => ['*'],
                'description' => 'Full access to all resources',
            ],
            [
                'name' => 'Admin',
                'permissions' => [
                    'customers.view',
                    'customers.create',
                    'customers.edit',
                    'customers.delete',
                    'billing.manage',
                    'routers.manage',
                    'reports.view',
                    'users.manage',
                ],
                'description' => 'Administrative access',
            ],
            [
                'name' => 'Billing',
                'permissions' => [
                    'customers.view',
                    'billing.manage',
                    'invoices.view',
                    'invoices.create',
                    'payments.view',
                ],
                'description' => 'Billing and invoices management',
            ],
            [
                'name' => 'Support',
                'permissions' => [
                    'customers.view',
                    'customers.edit',
                    'routers.view',
                    'tickets.view',
                    'tickets.manage',
                ],
                'description' => 'Customer support access',
            ],
            [
                'name' => 'NOC',
                'permissions' => [
                    'routers.view',
                    'routers.manage',
                    'network.view',
                    'network.manage',
                    'reports.view',
                ],
                'description' => 'Network operations center access',
            ],
        ];
    }
}
