<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkybaseAccount extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['features' => 'array', 'trial_ends_at' => 'datetime', 'paid_through' => 'datetime', 'cancel_at_period_end' => 'boolean', 'monthly_price_minor' => 'integer', 'subscriber_limit' => 'integer'];
    }
}
