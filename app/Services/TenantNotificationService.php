<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\TenantNotification;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TenantDatabaseNotification;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TenantNotificationService
{
    public function __construct(private NotificationPreferenceService $preferences) {}

    /**
     * @param  iterable<int, Model>  $recipients
     * @param  array<string, mixed>  $payload
     */
    public function notify(iterable $recipients, string $eventKey, array $payload, ?Model $related = null): void
    {
        $event = NotificationEventRegistry::event($eventKey);
        $tenantId = $this->tenantIdFrom($related, $recipients);

        if (! $tenantId) {
            return;
        }

        foreach ($recipients as $recipient) {
            if (! $recipient instanceof Model || (string) ($recipient->tenant_id ?? '') !== (string) $tenantId) {
                continue;
            }

            if (! $this->preferences->shouldDeliverInApp($recipient, $eventKey, (bool) $event['critical'])) {
                continue;
            }

            TenantNotification::query()->create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'type' => TenantDatabaseNotification::class,
                'notifiable_type' => $recipient->getMorphClass(),
                'notifiable_id' => $recipient->getKey(),
                'data' => array_merge([
                    'event_key' => $eventKey,
                    'title' => $event['label'],
                    'body' => '',
                    'category' => $event['category'],
                    'severity' => $event['severity'],
                    'action_url' => null,
                    'related_type' => $related?->getMorphClass(),
                    'related_id' => $related?->getKey(),
                ], $payload),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyAdmins(string $tenantId, string $eventKey, array $payload, ?Model $related = null): void
    {
        $roles = NotificationEventRegistry::event($eventKey)['roles'];

        $recipients = User::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->when($roles !== [], fn ($query) => $query->whereIn('role', $roles))
            ->get();

        $this->notify($recipients, $eventKey, $payload, $related);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyCustomer(Customer $customer, string $eventKey, array $payload, ?Model $related = null): void
    {
        $this->notify([$customer], $eventKey, $payload, $related);
    }

    public function ticketCreated(Ticket $ticket): void
    {
        $this->notifyAdmins($ticket->tenant_id, NotificationEventRegistry::TICKET_CREATED, [
            'title' => 'New support ticket',
            'body' => "{$ticket->ticket_number}: {$ticket->subject}",
            'action_url' => route('support.tickets.show', $ticket),
        ], $ticket);
    }

    public function ticketCustomerReply(Ticket $ticket, TicketMessage $message): void
    {
        $this->notify($this->ticketStaffRecipients($ticket), NotificationEventRegistry::TICKET_CUSTOMER_REPLY, [
            'title' => 'Customer replied to a ticket',
            'body' => "{$ticket->ticket_number}: {$ticket->subject}",
            'action_url' => route('support.tickets.show', $ticket),
            'related_type' => $message->getMorphClass(),
            'related_id' => $message->getKey(),
        ], $ticket);
    }

    public function ticketStaffReply(Ticket $ticket, TicketMessage $message): void
    {
        $ticket->loadMissing('customer');

        if (! $ticket->customer) {
            return;
        }

        $this->notifyCustomer($ticket->customer, NotificationEventRegistry::TICKET_STAFF_REPLY, [
            'title' => 'Your support ticket has a new reply',
            'body' => "{$ticket->ticket_number}: {$ticket->subject}",
            'action_url' => route('customer.support.show', $ticket),
            'category' => 'support',
            'related_type' => $message->getMorphClass(),
            'related_id' => $message->getKey(),
        ], $ticket);
    }

    private function ticketStaffRecipients(Ticket $ticket): Collection
    {
        $ticket->loadMissing('assignedUser', 'team.users');
        $recipients = collect();

        if ($ticket->assignedUser) {
            $recipients->push($ticket->assignedUser);
        }

        if ($ticket->team) {
            $recipients = $recipients->merge($ticket->team->users);
        }

        if ($recipients->isEmpty()) {
            $recipients = User::query()
                ->where('tenant_id', $ticket->tenant_id)
                ->where('status', 'active')
                ->whereIn('role', ['owner', 'admin', 'support'])
                ->get();
        }

        return $recipients->unique(fn (User $user): int => $user->id)->values();
    }

    /**
     * @param  iterable<int, Model>  $recipients
     */
    private function tenantIdFrom(?Model $related, iterable $recipients): ?string
    {
        if ($related && isset($related->tenant_id)) {
            return (string) $related->tenant_id;
        }

        if ($recipients instanceof EloquentCollection || $recipients instanceof Collection) {
            return $recipients->first()?->tenant_id ? (string) $recipients->first()->tenant_id : tenant_id();
        }

        foreach ($recipients as $recipient) {
            return $recipient?->tenant_id ? (string) $recipient->tenant_id : tenant_id();
        }

        return tenant_id();
    }
}
