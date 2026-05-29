<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Models\TicketEvent;

class TicketEventService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(Ticket $ticket, string $eventType, ?array $oldValues = null, ?array $newValues = null, string $actorType = 'system', ?int $actorId = null): TicketEvent
    {
        return TicketEvent::create([
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'event_type' => $eventType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
