<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GolestanSyncRun extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = ['tenant_id', 'user_id', 'preview_token', 'status', 'total', 'snapshot', 'outcomes'];

    protected $hidden = ['snapshot', 'preview_token'];

    protected function casts(): array
    {
        return ['snapshot' => 'encrypted:array', 'outcomes' => 'array'];
    }
}
