<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['channels' => 'array', 'content' => 'array', 'audience_filters' => 'array', 'recipient_ids' => 'array', 'scheduled_at' => 'datetime', 'materialized_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MessageDelivery::class);
    }
}
