<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreTicketMessageRequest;
use App\Http\Requests\Support\StoreTicketRequest;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketTeam;
use App\Models\User;
use App\Services\Tickets\TicketEventService;
use App\Services\Tickets\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Ticket::class);

        $user = $request->user();
        $tickets = $this->visibleTickets($user)
            ->with(['customer', 'team', 'assignedUser', 'subscription'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->input('priority')))
            ->when($request->filled('team'), fn ($query) => $query->where('ticket_team_id', $request->integer('team')))
            ->when($request->filled('assigned'), function ($query) use ($request): void {
                $request->input('assigned') === 'unassigned'
                    ? $query->whereNull('assigned_user_id')
                    : $query->where('assigned_user_id', $request->integer('assigned'));
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest('last_activity_at')
            ->paginate(20)
            ->withQueryString();

        return view('support.tickets.index', [
            'tickets' => $tickets,
            'teams' => TicketTeam::query()->orderBy('sort_order')->orderBy('name')->get(),
            'agents' => User::query()->where('tenant_id', tenant_id())->whereIn('role', ['owner', 'support', 'noc', 'admin'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Ticket::class);

        return view('support.tickets.create', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'email']),
            'teams' => TicketTeam::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'subscriptionsByCustomer' => Subscription::query()
                ->with('plan')
                ->latest()
                ->get()
                ->groupBy('customer_id')
                ->map(fn ($subscriptions) => $subscriptions->map(fn (Subscription $subscription): array => [
                    'id' => $subscription->id,
                    'label' => trim($subscription->subscription_code.' - '.($subscription->plan?->name ?? ucfirst($subscription->status))),
                ])->values())
                ->toArray(),
        ]);
    }

    public function store(StoreTicketRequest $request, TicketService $tickets): RedirectResponse
    {
        Gate::authorize('create', Ticket::class);

        $validated = $request->validated();
        $customer = Customer::query()->whereKey($validated['customer_id'])->firstOrFail();
        $team = TicketTeam::query()->whereKey($validated['ticket_team_id'])->firstOrFail();
        $ticket = $tickets->createFromUser($request->user(), $customer, $team, $validated, $this->attachments($request));

        return redirect()
            ->route('support.tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} created.");
    }

    public function show(Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $ticket->load([
            'customer',
            'subscription.plan',
            'team.users',
            'assignedUser',
            'messages.attachments',
            'events',
        ]);

        return view('support.tickets.show', [
            'ticket' => $ticket,
            'teams' => TicketTeam::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'agents' => User::query()->where('tenant_id', tenant_id())->whereIn('role', ['owner', 'support', 'noc', 'admin'])->active()->orderBy('name')->get(),
            'timeline' => $ticket->messages->concat($ticket->events)->sortBy('created_at'),
        ]);
    }

    public function reply(StoreTicketMessageRequest $request, Ticket $ticket, TicketService $tickets): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $tickets->addStaffMessage(
            ticket: $ticket,
            user: $request->user(),
            body: $request->validated('body'),
            visibility: $request->validated('visibility'),
            attachments: $this->attachments($request),
        );

        return back()->with('success', 'Reply added.');
    }

    public function updateStatus(Request $request, Ticket $ticket, TicketService $tickets): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $validated = $request->validate([
            'status' => ['required', Rule::in([Ticket::STATUS_OPEN, Ticket::STATUS_PENDING_CUSTOMER, Ticket::STATUS_PENDING_STAFF, Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])],
        ]);

        $tickets->changeStatus($ticket, $validated['status'], 'ticket.status_changed', 'user', $request->user()->id);

        return back()->with('success', 'Ticket status updated.');
    }

    public function updatePriority(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $validated = $request->validate([
            'priority' => ['required', Rule::in([Ticket::PRIORITY_LOW, Ticket::PRIORITY_NORMAL, Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT])],
        ]);

        $oldPriority = $ticket->priority;
        $ticket->forceFill([
            'priority' => $validated['priority'],
            'last_activity_at' => now(),
        ])->save();

        app(TicketEventService::class)->record(
            ticket: $ticket,
            eventType: 'ticket.priority_changed',
            oldValues: ['priority' => $oldPriority],
            newValues: ['priority' => $validated['priority']],
            actorType: 'user',
            actorId: $request->user()->id,
        );

        return back()->with('success', 'Ticket priority updated.');
    }

    public function assign(Request $request, Ticket $ticket, TicketService $tickets): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $validated = $request->validate([
            'assigned_user_id' => [
                'nullable',
                Rule::exists(User::class, 'id')->where(fn ($query) => $query->where('tenant_id', tenant_id())->where('status', 'active')),
            ],
        ]);

        $user = isset($validated['assigned_user_id'])
            ? User::query()->where('tenant_id', tenant_id())->whereKey($validated['assigned_user_id'])->first()
            : null;

        $tickets->assign($ticket, $user, $request->user());

        return back()->with('success', 'Ticket assignment updated.');
    }

    public function moveTeam(Request $request, Ticket $ticket, TicketService $tickets): RedirectResponse
    {
        Gate::authorize('update', $ticket);

        $validated = $request->validate([
            'ticket_team_id' => [
                'required',
                Rule::exists(TicketTeam::class, 'id')->where(fn ($query) => $query->where('tenant_id', tenant_id())->where('status', 'active')),
            ],
        ]);

        $team = TicketTeam::query()->whereKey($validated['ticket_team_id'])->firstOrFail();
        $tickets->moveTeam($ticket, $team, $request->user());

        return back()->with('success', 'Ticket team updated.');
    }

    public function download(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $ticket);

        abort_unless((int) $attachment->ticket_id === (int) $ticket->id, 404);
        abort_unless($attachment->existsOnDisk(), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->downloadName());
    }

    private function visibleTickets(User $user)
    {
        $query = Ticket::query();

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($query) use ($user): void {
            $query->where('assigned_user_id', $user->id)
                ->orWhereHas('team.users', fn ($query) => $query->where('users.id', $user->id)->where('ticket_team_user.is_active', true));
        });
    }

    /**
     * @return list<UploadedFile>
     */
    private function attachments(Request $request): array
    {
        return array_values($request->file('attachments', []) ?: []);
    }
}
