<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketEvent>
 */
class TicketEventFactory extends Factory
{
    public function definition(): array
    {
        $ticket = Ticket::factory()->create();

        return [
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'actor_type' => 'system',
            'actor_id' => null,
            'event_type' => 'ticket.created',
            'old_values' => null,
            'new_values' => ['status' => $ticket->status],
        ];
    }
}
