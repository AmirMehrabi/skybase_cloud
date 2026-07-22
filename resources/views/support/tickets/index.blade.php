@extends('layouts.admin')

@section('title', 'Support Tickets')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-950">Support Tickets</h1>
            <p class="text-sm text-slate-600">Triage customer requests, internal notes, assignments, and SLA risk.</p>
        </div>
        <a href="{{ route('support.tickets.create') }}" class="inline-flex items-center justify-center rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#123f3d]">New ticket</a>
    </div>

    <form method="GET" class="space-y-4 rounded-2xl border border-slate-900/10 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex gap-1 rounded-lg bg-slate-100 p-1">
                <a href="{{ route('support.tickets.index', array_merge(request()->except(['page', 'scope']), ['scope' => 'team'])) }}" class="rounded-md px-4 py-1.5 text-sm font-semibold transition {{ $viewScope === 'team' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">Team tickets</a>
                <a href="{{ route('support.tickets.index', array_merge(request()->except(['page', 'scope']), ['scope' => 'mine'])) }}" class="rounded-md px-4 py-1.5 text-sm font-semibold transition {{ $viewScope === 'mine' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">My tickets</a>
            </div>
            <p class="text-xs text-slate-500">Sorted by most recent activity</p>
        </div>
        <input type="hidden" name="scope" value="{{ $viewScope }}">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1.5fr)_repeat(4,minmax(0,1fr))_auto]">
            <label class="sr-only" for="ticket-search">Search tickets</label>
            <input id="ticket-search" name="search" value="{{ request('search') }}" placeholder="Search ticket, customer, PPPoE, or subscription" class="min-w-0 rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
            <label class="sr-only" for="ticket-status">Status</label>
            <select id="ticket-status" name="status" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                <option value="">All statuses</option>
                @foreach(['new', 'open', 'pending_customer', 'pending_staff', 'resolved', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="ticket-priority">Priority</label>
            <select id="ticket-priority" name="priority" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                <option value="">All priorities</option>
                @foreach(['low', 'normal', 'high', 'urgent'] as $priority)
                    <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="ticket-team">Team</label>
            <select id="ticket-team" name="team" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                <option value="">All teams</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}" @selected((string) request('team') === (string) $team->id)>{{ $team->name }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="ticket-assigned">Assignee</label>
            <select id="ticket-assigned" name="assigned" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
                <option value="">Any assignee</option>
                <option value="unassigned" @selected(request('assigned') === 'unassigned')>Unassigned</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" @selected((string) request('assigned') === (string) $agent->id)>{{ $agent->name }}</option>
                @endforeach
            </select>
            <button class="rounded-lg border border-slate-900/10 bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#123f3d]">Filter</button>
        </div>
    </form>

    @if(request()->hasAny(['search', 'status', 'priority', 'team', 'assigned']))
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
            <span class="font-semibold text-slate-500">Active filters:</span>
            @foreach(['search' => 'Search', 'status' => 'Status', 'priority' => 'Priority', 'team' => 'Team', 'assigned' => 'Assignee'] as $key => $label)
                @if(request($key) !== null && request($key) !== '')
                    <span class="rounded-full bg-white px-3 py-1.5 font-medium shadow-sm ring-1 ring-slate-900/10">{{ $label }}: {{ $key === 'status' || $key === 'priority' ? str(request($key))->replace('_', ' ')->headline() : request($key) }}</span>
                @endif
            @endforeach
            <a href="{{ route('support.tickets.index', ['scope' => $viewScope]) }}" class="font-semibold text-[#0d2f35] hover:underline">Clear all</a>
        </div>
    @endif

    <div class="hidden overflow-hidden rounded-xl border border-slate-900/10 bg-white shadow-sm md:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-900/10 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3">Ticket</th>
                        <th class="whitespace-nowrap px-4 py-3">Customer</th>
                        <th class="whitespace-nowrap px-4 py-3">Subscription</th>
                        <th class="whitespace-nowrap px-4 py-3">Team</th>
                        <th class="whitespace-nowrap px-4 py-3">Assignee</th>
                        <th class="whitespace-nowrap px-4 py-3">Status</th>
                        <th class="whitespace-nowrap px-4 py-3">SLA</th>
                        <th class="whitespace-nowrap px-4 py-3">Activity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-900/10">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3">
                                <a href="{{ route('support.tickets.show', $ticket) }}" class="font-semibold text-[#0d2f35]">{{ $ticket->ticket_number }}</a>
                                <div class="max-w-xs truncate text-slate-600">{{ $ticket->subject }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $ticket->priority === 'urgent' ? 'Urgent attention' : ucfirst($ticket->priority).' priority' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3"><div class="font-medium text-slate-900">{{ $ticket->customer?->full_name }}</div><div class="text-xs text-slate-500">{{ $ticket->customer?->email }}</div></td>
                            <td class="whitespace-nowrap px-4 py-3"><div class="font-medium text-slate-900">{{ $ticket->subscription?->subscription_code }}</div><div class="text-xs text-slate-500">{{ $ticket->subscription?->pppoe_username }}</div></td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $ticket->team?->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $ticket->assignedUser?->name ?? 'Queue' }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str($ticket->status)->replace('_', ' ')->headline() }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3">@php($slaClass = ['breached' => 'bg-red-100 text-red-700', 'at_risk' => 'bg-amber-100 text-amber-700'][$ticket->sla_state] ?? 'bg-emerald-100 text-emerald-700')<span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $slaClass }}">{{ str($ticket->sla_state)->replace('_', ' ')->headline() }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $ticket->last_activity_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">No tickets match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-3 md:hidden">
        @forelse($tickets as $ticket)
            <a href="{{ route('support.tickets.show', $ticket) }}" class="rounded-xl border border-slate-900/10 bg-white p-4 shadow-sm transition hover:border-[#0d2f35]/30 hover:shadow-md">
                <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $ticket->ticket_number }}</p><h2 class="mt-1 truncate font-semibold text-slate-950">{{ $ticket->subject }}</h2></div><span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str($ticket->status)->replace('_', ' ')->headline() }}</span></div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-xs"><div><p class="text-slate-500">Customer</p><p class="mt-0.5 truncate font-medium text-slate-900">{{ $ticket->customer?->full_name ?? 'Unknown' }}</p></div><div><p class="text-slate-500">Service</p><p class="mt-0.5 truncate font-medium text-slate-900">{{ $ticket->subscription?->pppoe_username ?: ($ticket->subscription?->subscription_code ?? 'None') }}</p></div><div><p class="text-slate-500">Owner</p><p class="mt-0.5 truncate font-medium text-slate-900">{{ $ticket->assignedUser?->name ?? 'Team queue' }}</p></div><div><p class="text-slate-500">Activity</p><p class="mt-0.5 font-medium text-slate-900">{{ $ticket->last_activity_at?->diffForHumans() }}</p></div></div>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs"><span class="font-semibold {{ $ticket->priority === 'urgent' ? 'text-red-700' : 'text-slate-600' }}">{{ ucfirst($ticket->priority) }} priority</span><span class="rounded-full px-2.5 py-1 font-semibold {{ ['breached' => 'bg-red-100 text-red-700', 'at_risk' => 'bg-amber-100 text-amber-700'][$ticket->sla_state] ?? 'bg-emerald-100 text-emerald-700' }}">{{ str($ticket->sla_state)->replace('_', ' ')->headline() }}</span></div>
            </a>
        @empty
            <div class="rounded-xl border border-slate-900/10 bg-white p-10 text-center text-sm text-slate-500 shadow-sm">No tickets match the current filters.</div>
        @endforelse
    </div>

    {{ $tickets->links() }}
</div>
@endsection
