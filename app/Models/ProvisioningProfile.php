<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvisioningProfile extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['parameters'];

    protected function casts(): array
    {
        return ['parameters' => 'encrypted:array'];
    }
}
