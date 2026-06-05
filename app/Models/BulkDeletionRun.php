<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkDeletionRun extends Model
{
    use HasFactory;

    public const MODULE_CUSTOMERS = 'customers';

    public const MODULE_SUBSCRIPTIONS = 'subscriptions';

    public const ACTION_DELETE = 'delete';

    public const SELECTION_SELECTED = 'selected';

    public const SELECTION_ALL = 'all';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'module',
        'action',
        'selection_mode',
        'filters',
        'selected_ids',
        'excluded_ids',
        'status',
        'total_count',
        'processed_count',
        'deleted_count',
        'failed_count',
        'summary',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'selected_ids' => 'array',
            'excluded_ids' => 'array',
            'summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_count' => 'integer',
            'processed_count' => 'integer',
            'deleted_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $tenantId = tenant_id() ?? auth()->user()?->tenant_id;

            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
        });

        static::creating(function (BulkDeletionRun $run): void {
            if (empty($run->tenant_id)) {
                $run->tenant_id = tenant_id() ?? auth()->user()?->tenant_id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'error' => null,
        ]);
    }

    public function markCompleted(array $summary = [], int $processedCount = 0, int $deletedCount = 0, int $failedCount = 0): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'finished_at' => now(),
            'processed_count' => $processedCount,
            'deleted_count' => $deletedCount,
            'failed_count' => $failedCount,
            'summary' => $summary,
            'error' => null,
        ]);
    }

    public function markFailed(string $error, array $summary = [], int $processedCount = 0, int $deletedCount = 0, int $failedCount = 0): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'finished_at' => now(),
            'processed_count' => $processedCount,
            'deleted_count' => $deletedCount,
            'failed_count' => $failedCount,
            'summary' => $summary,
            'error' => $error,
        ]);
    }
}
