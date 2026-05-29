<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        $customer = Customer::factory()->create();
        $team = TicketTeam::factory()->create(['tenant_id' => $customer->tenant_id]);

        return [
            'tenant_id' => $customer->tenant_id,
            'ticket_number' => 'TCK-'.now()->format('ymd').'-'.fake()->unique()->numberBetween(1000, 9999),
            'customer_id' => $customer->id,
            'subscription_id' => null,
            'ticket_team_id' => $team->id,
            'assigned_user_id' => null,
            'opened_by_customer_id' => $customer->id,
            'opened_by_user_id' => null,
            'source' => 'customer_portal',
            'subject' => fake()->sentence(6),
            'priority' => Ticket::PRIORITY_NORMAL,
            'status' => Ticket::STATUS_NEW,
            'first_response_due_at' => now()->addHours(4),
            'resolution_due_at' => now()->addDays(2),
            'last_customer_reply_at' => now(),
            'last_activity_at' => now(),
        ];
    }
}
