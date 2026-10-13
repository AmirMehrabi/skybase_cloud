<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use App\Models\Scopes\TenantScope;
use Database\Factories\TicketMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    use BelongsToTenant;
    use BelongsToUserGroup;

    /** @use HasFactory<TicketMessageFactory> */
    use HasFactory;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_INTERNAL = 'internal';

    protected $fillable = [
        'tenant_id',
        'ticket_id',
        'author_type',
        'author_id',
        'body',
        'visibility',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', TenantScope::apply(...));
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function authorName(): string
    {
        if ($this->author_type === 'customer' && $this->author_id) {
            return Customer::withoutGlobalScopes()->where('tenant_id', $this->tenant_id)->find($this->author_id)?->full_name ?? 'Customer';
        }

        if ($this->author_type === 'user' && $this->author_id) {
            return User::query()->where('tenant_id', $this->tenant_id)->find($this->author_id)?->name ?? 'Staff';
        }

        return 'System';
    }
}
