<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcsConnection extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'encrypted', 'automation_enabled' => 'boolean', 'last_synced_at' => 'datetime', 'last_tested_at' => 'datetime'];
    }
}
