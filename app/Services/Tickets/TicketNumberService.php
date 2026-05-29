<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketNumberService
{
    public function next(string $tenantId): string
    {
        $prefix = 'TCK-'.now()->format('ymd');

        $latestNumber = Ticket::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('ticket_number', 'like', $prefix.'-%')
            ->lockForUpdate()
            ->orderByDesc('ticket_number')
            ->value('ticket_number');

        $sequence = $latestNumber
            ? ((int) str($latestNumber)->afterLast('-')->toString()) + 1
            : 1;

        return sprintf('%s-%04d', $prefix, $sequence);
    }

    public function reserve(string $tenantId): string
    {
        return DB::transaction(fn (): string => $this->next($tenantId));
    }
}
