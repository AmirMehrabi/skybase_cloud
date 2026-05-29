<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Models\TicketTeam;

class TicketSlaService
{
    /**
     * @return array<string, mixed>
     */
    public function dueDatesFor(TicketTeam $team): array
    {
        $now = now();

        return [
            'first_response_due_at' => $team->first_response_minutes > 0
                ? $now->copy()->addMinutes($team->first_response_minutes)
                : null,
            'resolution_due_at' => $team->resolution_minutes > 0
                ? $now->copy()->addMinutes($team->resolution_minutes)
                : null,
        ];
    }

    public function markFirstStaffResponse(Ticket $ticket): void
    {
        if ($ticket->first_staff_response_at) {
            return;
        }

        $ticket->forceFill([
            'first_staff_response_at' => now(),
        ])->save();
    }
}
