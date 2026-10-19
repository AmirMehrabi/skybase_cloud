<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageDelivery extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['content' => 'encrypted:array', 'destination' => 'encrypted', 'context' => 'array', 'available_at' => 'datetime', 'expires_at' => 'datetime', 'claimed_at' => 'datetime', 'accepted_at' => 'datetime', 'delivered_at' => 'datetime', 'receipt_checked_at' => 'datetime'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(NotificationIncident::class, 'notification_incident_id');
    }
}
