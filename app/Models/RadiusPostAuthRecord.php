<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class RadiusPostAuthRecord extends Model
{
    use BelongsToTenant;
    use BelongsToUserGroup;
    use MassPrunable;

    public const RETENTION_MINUTES = 5;

    protected $table = 'radpostauth';

    public $timestamps = false;

    protected $hidden = ['pass'];

    protected $fillable = [
        'tenant_id',
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

    /** Central retention cleanup removes expired secret-bearing records across tenants. */
    public function prunable(): Builder
    {
        return static::withoutGlobalScopes()
            ->where('authdate', '<=', now()->subMinutes(self::RETENTION_MINUTES));
    }
}
