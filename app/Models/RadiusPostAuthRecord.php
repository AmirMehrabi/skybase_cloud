<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class RadiusPostAuthRecord extends Model
{
    use BelongsToUserGroup;
    use MassPrunable;

    public const RETENTION_MINUTES = 5;

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
            ->where('authdate', '<=', now()->subMinutes(self::RETENTION_MINUTES));
    }
}
