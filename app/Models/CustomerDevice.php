<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerDevice extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['last_contact_at' => 'datetime', 'capabilities' => 'array'];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(AcsConnection::class, 'acs_connection_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ProvisioningProfile::class, 'provisioning_profile_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(DeviceAction::class);
    }
}
