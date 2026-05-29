<?php

namespace App\Services\Tickets;

use App\Models\TicketTeam;
use App\Models\User;

class TicketAssignmentService
{
    public function assigneeFor(TicketTeam $team): ?User
    {
        return match ($team->assignment_strategy) {
            TicketTeam::STRATEGY_RANDOM => $this->randomAgent($team),
            TicketTeam::STRATEGY_DEFAULT_AGENT => $this->defaultAgent($team),
            default => null,
        };
    }

    private function randomAgent(TicketTeam $team): ?User
    {
        return $team->activeAutoAssignableUsers()
            ->inRandomOrder()
            ->first();
    }

    private function defaultAgent(TicketTeam $team): ?User
    {
        if (! $team->default_user_id) {
            return null;
        }

        return $team->users()
            ->where('users.id', $team->default_user_id)
            ->where('users.status', 'active')
            ->wherePivot('is_active', true)
            ->first();
    }
}
