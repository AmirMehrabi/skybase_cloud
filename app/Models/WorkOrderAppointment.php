<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToUserGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderAppointment extends Model
{
    use BelongsToTenant, HasFactory;
    use BelongsToUserGroup;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'confirmed_at' => 'datetime',
            'arrived_at' => 'datetime', 'started_at' => 'datetime', 'ended_at' => 'datetime',
        ];
    }
}
