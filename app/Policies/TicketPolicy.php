<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canWorkTickets($user);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $this->sameTenant($user, $ticket) && (
            $this->canWorkTickets($user)
            || (int) $ticket->assigned_user_id === (int) $user->id
            || $user->ticketTeams()
                ->where('ticket_teams.id', $ticket->ticket_team_id)
                ->where('ticket_team_user.is_active', true)
                ->exists()
        );
    }

    public function create(User $user): bool
    {
        return $this->canWorkTickets($user);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $this->canWorkTickets($user);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $this->sameTenant($user, $ticket) && $this->canManageAllTickets($user);
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return $this->delete($user, $ticket);
    }

    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $this->delete($user, $ticket);
    }

    private function sameTenant(User $user, Ticket $ticket): bool
    {
        return (string) $user->tenant_id === (string) $ticket->tenant_id;
    }

    private function canWorkTickets(User $user): bool
    {
        return $user->isAdmin()
            || in_array($user->role, ['support', 'noc'], true)
            || $user->hasPermission('support_tickets.read')
            || $user->hasPermission('support_tickets.write')
            || $user->hasPermission('support_tickets.actions');
    }

    private function canManageAllTickets(User $user): bool
    {
        return $user->isAdmin();
    }
}
