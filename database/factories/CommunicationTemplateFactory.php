<?php

namespace Database\Factories;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunicationTemplate> */
class CommunicationTemplateFactory extends Factory
{
    public function definition(): array
    {
        return ['tenant_id' => tenant_id(), 'name' => fake()->sentence(3), 'enabled' => false, 'recipient_type' => 'customer', 'channels' => ['email'], 'subject' => 'Hello {{customer_name}}', 'email_body' => '{{body}}', 'sms_body' => '{{body}}', 'sms_mode' => 'plain', 'recipients' => []];
    }
}
