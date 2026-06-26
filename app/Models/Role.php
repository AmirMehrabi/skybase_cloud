<?php

namespace App\Models;

use App\Support\Rbac\PermissionRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'permissions',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        return in_array('*', $permissions, true)
            || in_array($permission, $permissions, true);
    }

    public function normalizedName(): string
    {
        return Str::of($this->name)->lower()->replace(' ', '_')->toString();
    }

    public static function findForTenantRole(string $tenantId, string $role): ?self
    {
        return self::query()
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(name) = ?', [Str::lower($role)])
            ->first();
    }

    public static function ensureDefaultsForTenant(string $tenantId): void
    {
        foreach (self::getDefaultRoles() as $roleData) {
            $role = self::findForTenantRole($tenantId, $roleData['name']);

            if ($role) {
                if (blank($role->permissions)) {
                    $role->update(['permissions' => $roleData['permissions']]);
                }

                continue;
            }

            self::create([
                'tenant_id' => $tenantId,
                'name' => $roleData['name'],
                'permissions' => $roleData['permissions'],
                'description' => $roleData['description'],
            ]);
        }
    }

    public static function getDefaultRoles(): array
    {
        $defaults = PermissionRegistry::defaultRolePermissions();

        return [
            [
                'name' => 'Owner',
                'permissions' => $defaults['owner'],
                'description' => 'Full access to all resources',
            ],
            [
                'name' => 'Admin',
                'permissions' => $defaults['admin'],
                'description' => 'Administrative access',
            ],
            [
                'name' => 'Billing',
                'permissions' => $defaults['billing'],
                'description' => 'Billing and invoices management',
            ],
            [
                'name' => 'Support',
                'permissions' => $defaults['support'],
                'description' => 'Customer support access',
            ],
            [
                'name' => 'NOC',
                'permissions' => $defaults['noc'],
                'description' => 'Network operations center access',
            ],
        ];
    }
}
