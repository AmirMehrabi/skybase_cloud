<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserGroup;
use Database\Factories\TicketAttachmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    use BelongsToUserGroup;

    /** @use HasFactory<TicketAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'ticket_id',
        'ticket_message_id',
        'uploader_type',
        'uploader_id',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size',
        'visibility',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $tenantId = tenant_id() ?? auth()->user()?->tenant_id ?? auth('customer')->user()?->tenant_id;

            if ($tenantId) {
                $query->where($query->qualifyColumn('tenant_id'), $tenantId);
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    public function downloadName(): string
    {
        return $this->original_name ?: basename($this->path);
    }

    public function existsOnDisk(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }
}
