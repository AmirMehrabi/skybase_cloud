<?php

namespace Database\Factories;

use App\Models\NotificationIncident;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationIncident> */
class NotificationIncidentFactory extends Factory
{
    public function definition(): array
    {
        return ['tenant_id' => tenant_id(), 'router_id' => Router::factory(), 'status' => 'resolved', 'opened_at' => now()->subHour(), 'resolved_at' => now()];
    }
}
