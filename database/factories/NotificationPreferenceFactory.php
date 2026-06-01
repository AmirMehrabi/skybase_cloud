<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => tenant_id() ?? 'tenant-test',
            'notifiable_type' => User::class,
            'notifiable_id' => 1,
            'notifications_enabled' => true,
            'in_app_enabled' => true,
            'email_enabled' => false,
            'sms_enabled' => false,
        ];
    }
}
