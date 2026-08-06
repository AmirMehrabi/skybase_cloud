<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPortal\StoreTicketMessageRequest;
use App\Http\Requests\CustomerPortal\StoreTicketRequest;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\TicketTeam;
use App\Services\Tickets\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $customer = auth('customer')->user();
        $ticketView = $request->input('view') === 'closed' ? 'closed' : 'active';

        return view('customer.support.index', [
            'tickets' => Ticket::query()
                ->with(['team', 'assignedUser', 'subscription'])
                ->where('customer_id', $customer->id)
                ->when(
                    $ticketView === 'closed',
                    fn ($query) => $query->where('status', Ticket::STATUS_CLOSED),
                    fn ($query) => $query->where('status', '!=', Ticket::STATUS_CLOSED),
                )
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = $request->string('search')->toString();
                    $query->where(function ($query) use ($search): void {
                        $query->where('ticket_number', 'like', "%{$search}%")
                            ->orWhere('subject', 'like', "%{$search}%")
                            ->orWhereHas('subscription', fn ($query) => $query->where('subscription_code', 'like', "%{$search}%")->orWhere('pppoe_username', 'like', "%{$search}%"));
                    });
                })
                ->latest('last_activity_at')
                ->paginate(12)
                ->withQueryString(),
            'ticketView' => $ticketView,
        ]);
    }

    public function create(): View
    {
        $customer = auth('customer')->user();

        return view('customer.support.create', [
            'teams' => TicketTeam::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'subscriptions' => Subscription::query()->where('customer_id', $customer->id)->latest()->get(),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketService $tickets): RedirectResponse
    {
        $customer = auth('customer')->user();
        $validated = $request->validated();
        $team = TicketTeam::query()->whereKey($validated['ticket_team_id'])->firstOrFail();
        $ticket = $tickets->createFromCustomer($customer, $team, $validated, $this->attachments($request));

        return redirect()
            ->route('customer.support.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} submitted.");
    }

    public function show(Ticket $ticket): View
    {
        $this->authorizeCustomerTicket($ticket);

        $ticket->load(['team', 'assignedUser', 'subscription.plan', 'messages.attachments']);

        return view('customer.support.show', [
            'ticket' => $ticket,
            'messages' => $ticket->messages
                ->where('visibility', TicketMessage::VISIBILITY_PUBLIC)
                ->sortBy('created_at'),
        ]);
    }

    public function reply(StoreTicketMessageRequest $request, Ticket $ticket, TicketService $tickets): RedirectResponse
    {
        $this->authorizeCustomerTicket($ticket);

        $tickets->addCustomerReply(
            ticket: $ticket,
            customer: auth('customer')->user(),
            body: $request->validated('body'),
            attachments: $this->attachments($request),
        );

        return back()->with('success', 'Reply added.');
    }

    public function close(Ticket $ticket, TicketService $tickets): RedirectResponse
    {
        $this->authorizeCustomerTicket($ticket);

        $tickets->changeStatus($ticket, Ticket::STATUS_CLOSED, 'ticket.closed_by_customer', 'customer', auth('customer')->id());

        return redirect()
            ->route('customer.support.show', $ticket)
            ->with('success', 'Ticket closed.');
    }

    public function download(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $this->authorizeCustomerTicket($ticket);

        abort_unless((int) $attachment->ticket_id === (int) $ticket->id, 404);
        abort_unless($attachment->visibility === TicketMessage::VISIBILITY_PUBLIC, 403);
        abort_unless($attachment->existsOnDisk(), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->downloadName());
    }

    private function authorizeCustomerTicket(Ticket $ticket): void
    {
        $customer = auth('customer')->user();

        abort_unless(
            (string) $ticket->tenant_id === (string) $customer->tenant_id
            && (int) $ticket->customer_id === (int) $customer->id,
            403
        );
    }

    /**
     * @return list<UploadedFile>
     */
    private function attachments(Request $request): array
    {
        return array_values($request->file('attachments', []) ?: []);
    }
}
