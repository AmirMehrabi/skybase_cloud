<?php

namespace Database\Factories;

use App\Models\MessageDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MessageDelivery> */
class MessageDeliveryFactory extends Factory
{
    public function definition(): array
    {
        return ['tenant_id' => tenant_id(), 'idempotency_key' => hash('sha256', fake()->uuid()), 'channel' => 'sms', 'destination' => '+12025550100', 'content' => ['sms_body' => 'Example message'], 'status' => 'simulated', 'available_at' => now()];
    }
}
