<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketAttachment>
 */
class TicketAttachmentFactory extends Factory
{
    public function definition(): array
    {
        $ticket = Ticket::factory()->create();
        $message = TicketMessage::factory()->create(['ticket_id' => $ticket->id, 'tenant_id' => $ticket->tenant_id]);

        return [
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'ticket_message_id' => $message->id,
            'uploader_type' => 'customer',
            'uploader_id' => $ticket->customer_id,
            'original_name' => 'diagnostic.txt',
            'disk' => 'public',
            'path' => 'tickets/testing/diagnostic.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
            'visibility' => 'public',
        ];
    }
}
