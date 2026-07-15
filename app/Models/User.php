<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\LogsTenantActivity;
use App\Support\Rbac\PermissionRegistry;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsTenantActivity, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function activityLogExcept(): array
    {
        return ['password', 'remember_token', 'updated_at'];
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->tenant_id)) {
                $user->tenant_id = tenant_id() ?? auth()->user()?->tenant_id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'name')
            ->where('tenant_id', $this->tenant_id);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function ticketTeams(): BelongsToMany
    {
        return $this->belongsToMany(TicketTeam::class)
            ->withPivot(['tenant_id', 'is_active', 'accepts_auto_assignment'])
            ->withTimestamps();
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_user_id');
    }

    public function customerNotes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(UserSetting::class);
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public function setSetting(string $key, mixed $value): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    public function getRoleDisplayName(): string
    {
        $role = $this->resolvedRole();

        if ($role) {
            return $role->name;
        }

        return match ($this->normalizedRoleName()) {
            'owner' => 'Owner',
            'admin' => 'Administrator',
            'billing' => 'Billing Manager',
            'support' => 'Support Agent',
            'noc' => 'NOC Engineer',
            default => 'User',
        };
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->normalizedRoleName() === 'owner') {
            return true;
        }

        $role = $this->resolvedRole();

        $permissions = PermissionRegistry::equivalentPermissions($permission);

        if (! $role) {
            $permissions = PermissionRegistry::defaultRolePermissions()[$this->normalizedRoleName()] ?? [];

            return in_array('*', $permissions, true)
                || collect(PermissionRegistry::equivalentPermissions($permission))
                    ->contains(fn (string $candidate): bool => in_array($candidate, $permissions, true));
        }

        return collect($permissions)->contains(fn (string $candidate): bool => $role->hasPermission($candidate));
    }

    public function canAccessRoute(?string $routeName): bool
    {
        $permission = PermissionRegistry::routePermission($routeName);

        return $permission === null || $this->hasPermission($permission);
    }

    public function isOwner(): bool
    {
        return $this->normalizedRoleName() === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->normalizedRoleName(), ['owner', 'admin'], true)
            || $this->hasPermission('roles.write');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function canAccessTenant(string $tenantId): bool
    {
        return (string) $this->tenant_id === $tenantId;
    }

    public function resolvedRole(): ?Role
    {
        if (! $this->tenant_id || ! $this->role) {
            return null;
        }

        return Role::findForTenantRole((string) $this->tenant_id, $this->role);
    }

    public function normalizedRoleName(): string
    {
        return Str::of((string) $this->role)->lower()->replace(' ', '_')->toString();
    }
}
