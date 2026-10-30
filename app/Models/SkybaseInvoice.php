<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkybaseInvoice extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['features' => 'array', 'paid_at' => 'datetime', 'simulated' => 'boolean', 'amount_minor' => 'integer', 'subscriber_limit' => 'integer'];
    }
}
