<?php

namespace App\Services\Tickets;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\TicketTeam;
use App\Models\User;
use App\Services\TenantNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(
        private TicketAssignmentService $assignmentService,
        private TicketNumberService $numberService,
        private TicketSlaService $slaService,
        private TicketEventService $eventService,
        private TenantNotificationService $notifications,
    ) {}

    /**
     * @param  list<UploadedFile>  $attachments
     * @param  array<string, mixed>  $data
     */
    public function createFromCustomer(Customer $customer, TicketTeam $team, array $data, array $attachments = []): Ticket
    {
        return DB::transaction(function () use ($customer, $team, $data, $attachments): Ticket {
            $ticket = $this->createTicket($customer, $team, $data, 'customer_portal', openedByCustomer: $customer);

            $message = $this->addMessage(
                ticket: $ticket,
                body: $data['message'],
                visibility: TicketMessage::VISIBILITY_PUBLIC,
                authorType: 'customer',
                authorId: $customer->id,
                attachments: $attachments,
            );

            $this->eventService->record($ticket, 'ticket.created', null, [
                'source' => 'customer_portal',
                'message_id' => $message->id,
            ], 'customer', $customer->id);
            $this->notifications->ticketCreated($ticket);

            return $ticket->fresh(['team', 'assignedUser', 'messages.attachments']);
        });
    }

    /**
     * @param  list<UploadedFile>  $attachments
     * @param  array<string, mixed>  $data
     */
    public function createFromUser(User $user, Customer $customer, TicketTeam $team, array $data, array $attachments = [], ?User $assignee = null): Ticket
    {
        return DB::transaction(function () use ($user, $customer, $team, $data, $attachments, $assignee): Ticket {
            $ticket = $this->createTicket($customer, $team, $data, 'admin_portal', openedByUser: $user, explicitAssignee: $assignee);

            $message = $this->addMessage(
                ticket: $ticket,
                body: $data['message'],
                visibility: TicketMessage::VISIBILITY_PUBLIC,
                authorType: 'user',
                authorId: $user->id,
                attachments: $attachments,
            );

            $this->eventService->record($ticket, 'ticket.created', null, [
                'source' => 'admin_portal',
                'message_id' => $message->id,
            ], 'user', $user->id);
            $this->notifications->ticketStaffReply($ticket->load('customer'), $message);

            return $ticket->fresh(['customer', 'team', 'assignedUser', 'messages.attachments']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createTicket(Customer $customer, TicketTeam $team, array $data, string $source, ?Customer $openedByCustomer = null, ?User $openedByUser = null, ?User $explicitAssignee = null): Ticket
    {
        $tenantId = (string) $customer->tenant_id;
        $assignee = $explicitAssignee ?? $this->assignmentService->assigneeFor($team);
        $dueDates = $this->slaService->dueDatesFor($team);

        return Ticket::create(array_merge($dueDates, [
            'tenant_id' => $tenantId,
            'ticket_number' => $this->numberService->next($tenantId),
            'customer_id' => $customer->id,
            'subscription_id' => $data['subscription_id'] ?? null,
            'ticket_team_id' => $team->id,
            'assigned_user_id' => $assignee?->id,
            'opened_by_customer_id' => $openedByCustomer?->id,
            'opened_by_user_id' => $openedByUser?->id,
            'source' => $source,
            'subject' => $data['subject'],
            'priority' => $data['priority'] ?? Ticket::PRIORITY_NORMAL,
            'status' => Ticket::STATUS_NEW,
            'last_customer_reply_at' => $openedByCustomer ? now() : null,
            'last_staff_reply_at' => $openedByUser ? now() : null,
            'last_activity_at' => now(),
        ]));
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function addCustomerReply(Ticket $ticket, Customer $customer, string $body, array $attachments = []): TicketMessage
    {
        return DB::transaction(function () use ($ticket, $customer, $body, $attachments): TicketMessage {
            if (in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true)) {
                $this->changeStatus($ticket, Ticket::STATUS_OPEN, 'ticket.reopened', 'customer', $customer->id);
            }

            $message = $this->addMessage($ticket, $body, TicketMessage::VISIBILITY_PUBLIC, 'customer', $customer->id, $attachments);

            $ticket->forceFill([
                'status' => Ticket::STATUS_PENDING_STAFF,
                'last_customer_reply_at' => now(),
                'last_activity_at' => now(),
            ])->save();
            $this->notifications->ticketCustomerReply($ticket, $message);

            return $message;
        });
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function addStaffMessage(Ticket $ticket, User $user, string $body, string $visibility, array $attachments = []): TicketMessage
    {
        return DB::transaction(function () use ($ticket, $user, $body, $visibility, $attachments): TicketMessage {
            $message = $this->addMessage($ticket, $body, $visibility, 'user', $user->id, $attachments);

            if ($visibility === TicketMessage::VISIBILITY_PUBLIC) {
                $this->slaService->markFirstStaffResponse($ticket);
                $ticket->forceFill([
                    'status' => Ticket::STATUS_PENDING_CUSTOMER,
                    'last_staff_reply_at' => now(),
                    'last_activity_at' => now(),
                ])->save();
                $this->notifications->ticketStaffReply($ticket, $message);
            } else {
                $ticket->forceFill(['last_activity_at' => now()])->save();
            }

            return $message;
        });
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    private function addMessage(Ticket $ticket, string $body, string $visibility, string $authorType, ?int $authorId, array $attachments = []): TicketMessage
    {
        $message = TicketMessage::create([
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'author_type' => $authorType,
            'author_id' => $authorId,
            'body' => $body,
            'visibility' => $visibility,
            'is_system' => false,
        ]);

        foreach ($attachments as $attachment) {
            $this->storeAttachment($ticket, $message, $attachment, $authorType, $authorId, $visibility);
        }

        return $message;
    }

    private function storeAttachment(Ticket $ticket, TicketMessage $message, UploadedFile $file, string $uploaderType, ?int $uploaderId, string $visibility): TicketAttachment
    {
        $path = $file->store("tickets/{$ticket->tenant_id}/{$ticket->ticket_number}", 'public');

        return TicketAttachment::create([
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'ticket_message_id' => $message->id,
            'uploader_type' => $uploaderType === 'customer' ? 'customer' : 'user',
            'uploader_id' => $uploaderId,
            'original_name' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'visibility' => $visibility,
        ]);
    }

    public function changeStatus(Ticket $ticket, string $status, string $eventType, string $actorType, ?int $actorId): void
    {
        $oldStatus = $ticket->status;
        $updates = [
            'status' => $status,
            'last_activity_at' => now(),
        ];

        if ($status === Ticket::STATUS_RESOLVED) {
            $updates['resolved_at'] = now();
        }

        if ($status === Ticket::STATUS_CLOSED) {
            $updates['closed_at'] = now();
        }

        if (! in_array($status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true)) {
            $updates['resolved_at'] = null;
            $updates['closed_at'] = null;
        }

        $ticket->forceFill($updates)->save();
        $this->eventService->record($ticket, $eventType, ['status' => $oldStatus], ['status' => $status], $actorType, $actorId);
    }

    public function updateAssignment(Ticket $ticket, TicketTeam $team, ?User $user, User $actor): void
    {
        $oldTeam = $ticket->ticket_team_id;
        $oldAssignee = $ticket->assigned_user_id;

        $ticket->forceFill([
            'ticket_team_id' => $team->id,
            'assigned_user_id' => $user?->id,
            'last_activity_at' => now(),
        ])->save();

        if ((int) $oldTeam !== (int) $team->id) {
            $this->eventService->record($ticket, 'ticket.team_changed', ['ticket_team_id' => $oldTeam], ['ticket_team_id' => $team->id], 'user', $actor->id);
        }

        if ((int) $oldAssignee !== (int) $user?->id) {
            $this->eventService->record($ticket, 'ticket.assigned', ['assigned_user_id' => $oldAssignee], ['assigned_user_id' => $user?->id], 'user', $actor->id);
        }
    }

    public function moveTeam(Ticket $ticket, TicketTeam $team, User $actor): void
    {
        $oldTeam = $ticket->ticket_team_id;
        $assignee = $this->assignmentService->assigneeFor($team);

        $ticket->forceFill([
            'ticket_team_id' => $team->id,
            'assigned_user_id' => $assignee?->id,
            'last_activity_at' => now(),
        ])->save();

        $this->eventService->record($ticket, 'ticket.team_changed', ['ticket_team_id' => $oldTeam], ['ticket_team_id' => $team->id], 'user', $actor->id);
    }

    public function subscriptionBelongsToCustomer(?int $subscriptionId, Customer $customer): bool
    {
        if (! $subscriptionId) {
            return true;
        }

        return Subscription::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->whereKey($subscriptionId)
            ->exists();
    }
}
