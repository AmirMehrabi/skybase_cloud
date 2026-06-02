<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportExportRunRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_export_run_id',
        'row_number',
        'status',
        'identifier',
        'action',
        'message',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'row_number' => 'integer',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ImportExportRun::class, 'import_export_run_id');
    }
}
