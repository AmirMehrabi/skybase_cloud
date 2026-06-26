<?php

namespace App\Policies;

use App\Models\TicketTeam;
use App\Models\User;

class TicketTeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageTeams($user);
    }

    public function view(User $user, TicketTeam $ticketTeam): bool
    {
        return $this->sameTenant($user, $ticketTeam) && (
            $this->canManageTeams($user)
            || $user->ticketTeams()->where('ticket_teams.id', $ticketTeam->id)->exists()
        );
    }

    public function create(User $user): bool
    {
        return $this->canManageTeams($user);
    }

    public function update(User $user, TicketTeam $ticketTeam): bool
    {
        return $this->sameTenant($user, $ticketTeam) && $this->canManageTeams($user);
    }

    public function delete(User $user, TicketTeam $ticketTeam): bool
    {
        return $this->update($user, $ticketTeam);
    }

    public function restore(User $user, TicketTeam $ticketTeam): bool
    {
        return $this->delete($user, $ticketTeam);
    }

    public function forceDelete(User $user, TicketTeam $ticketTeam): bool
    {
        return $this->delete($user, $ticketTeam);
    }

    private function sameTenant(User $user, TicketTeam $ticketTeam): bool
    {
        return (string) $user->tenant_id === (string) $ticketTeam->tenant_id;
    }

    private function canManageTeams(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('support_teams.write');
    }
}
