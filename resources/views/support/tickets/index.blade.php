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

    <form method="GET" class="grid gap-3 rounded-xl border border-slate-900/10 bg-white p-4 shadow-sm md:grid-cols-6">
        <input name="search" value="{{ request('search') }}" placeholder="Search ticket, customer, or subscription" class="rounded-lg border border-slate-300 px-3 py-2 text-sm md:col-span-2">
        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach(['new', 'open', 'pending_customer', 'pending_staff', 'resolved', 'closed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
            @endforeach
        </select>
        <select name="team" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All teams</option>
            @foreach($teams as $team)
                <option value="{{ $team->id }}" @selected((string) request('team') === (string) $team->id)>{{ $team->name }}</option>
            @endforeach
        </select>
        <select name="assigned" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Any assignee</option>
            <option value="unassigned" @selected(request('assigned') === 'unassigned')>Unassigned</option>
            @foreach($agents as $agent)
                <option value="{{ $agent->id }}" @selected((string) request('assigned') === (string) $agent->id)>{{ $agent->name }}</option>
            @endforeach
        </select>
        <button class="rounded-lg border border-slate-900/10 bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-900/10 bg-white shadow-sm">
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
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $ticket->customer?->full_name }}</div>
                                <div class="text-xs text-slate-500">{{ $ticket->customer?->email }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $ticket->subscription?->subscription_code }}</div>
                                <div class="text-xs text-slate-500">{{ $ticket->subscription?->pppoe_username }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $ticket->team?->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $ticket->assignedUser?->name ?? 'Queue' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str($ticket->status)->replace('_', ' ')->headline() }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @php($slaClass = ['breached' => 'bg-red-100 text-red-700', 'at_risk' => 'bg-amber-100 text-amber-700'][$ticket->sla_state] ?? 'bg-emerald-100 text-emerald-700')
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $slaClass }}">{{ str($ticket->sla_state)->replace('_', ' ')->headline() }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $ticket->last_activity_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-500">No tickets match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $tickets->links() }}
</div>
@endsection
