<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return ['tenant_id' => tenant_id(), 'name' => fake()->sentence(3), 'status' => 'draft', 'purpose' => 'service', 'channels' => ['sms'], 'content' => ['sms_body' => 'Hello {{customer_name}}'], 'audience_filters' => ['status' => 'active'], 'audience_mode' => 'snapshot', 'recipient_ids' => []];
    }
}
