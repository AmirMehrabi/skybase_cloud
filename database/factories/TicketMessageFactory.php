<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketMessage>
 */
class TicketMessageFactory extends Factory
{
    public function definition(): array
    {
        $ticket = Ticket::factory()->create();

        return [
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'author_type' => 'customer',
            'author_id' => $ticket->customer_id,
            'body' => fake()->paragraph(),
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'is_system' => false,
        ];
    }
}
