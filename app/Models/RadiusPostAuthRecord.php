<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class RadiusPostAuthRecord extends Model
{
    use MassPrunable;

    public const RETENTION_DAYS = 30;

    protected $table = 'radpostauth';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'pass',
        'reply',
        'authdate',
    ];

    protected function casts(): array
    {
        return [
            'authdate' => 'datetime',
        ];
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where('authdate', '<=', now()->subDays(self::RETENTION_DAYS));
    }
}
