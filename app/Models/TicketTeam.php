<?php

namespace App\Models;

use Database\Factories\TicketTeamFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TicketTeam extends Model
{
    /** @use HasFactory<TicketTeamFactory> */
    use HasFactory;

    public const STRATEGY_RANDOM = 'random';

    public const STRATEGY_DEFAULT_AGENT = 'default_agent';

    public const STRATEGY_QUEUE = 'queue';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'status',
        'assignment_strategy',
        'default_user_id',
        'first_response_minutes',
        'resolution_minutes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'first_response_minutes' => 'integer',
            'resolution_minutes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $tenantId = tenant_id() ?? auth()->user()?->tenant_id;

            if ($tenantId) {
                $query->where($query->qualifyColumn('tenant_id'), $tenantId);
            }
        });

        static::creating(function (TicketTeam $team): void {
            if (empty($team->tenant_id)) {
                $team->tenant_id = tenant_id() ?? auth()->user()?->tenant_id;
            }

            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function defaultUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['tenant_id', 'is_active', 'accepts_auto_assignment'])
            ->withTimestamps();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function activeAutoAssignableUsers(): BelongsToMany
    {
        return $this->users()
            ->where('users.status', 'active')
            ->wherePivot('is_active', true)
            ->wherePivot('accepts_auto_assignment', true);
    }
}
