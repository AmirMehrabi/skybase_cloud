@extends('layouts.customer')

@section('title', 'Support')
@section('page_title', 'Support')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-950">Support</h1>
            <p class="text-sm text-slate-600">Submit requests and track replies from the support team.</p>
        </div>
        <a href="{{ route('customer.support.create') }}" class="inline-flex items-center justify-center rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#123f3d]">New ticket</a>
    </div>

    <div class="space-y-4 rounded-2xl border border-slate-900/10 bg-white p-4 shadow-sm">
        <nav aria-label="Ticket views" class="flex w-fit gap-1 rounded-lg bg-slate-100 p-1">
            <a href="{{ route('customer.support.index', array_merge(request()->except(['page', 'view']), ['view' => 'active'])) }}" @if($ticketView === 'active') aria-current="page" @endif class="rounded-md px-4 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0d2f35] {{ $ticketView === 'active' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">Active tickets</a>
            <a href="{{ route('customer.support.index', array_merge(request()->except(['page', 'view']), ['view' => 'closed'])) }}" @if($ticketView === 'closed') aria-current="page" @endif class="rounded-md px-4 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0d2f35] {{ $ticketView === 'closed' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">Closed tickets</a>
        </nav>

        <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <input type="hidden" name="view" value="{{ $ticketView }}">
            <div class="min-w-0 flex-1">
                <label for="customer-ticket-search" class="block text-sm font-medium text-slate-700">Search tickets</label>
                <input id="customer-ticket-search" name="search" value="{{ request('search') }}" placeholder="Ticket number, subject, or service" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20">
            </div>
            <button class="rounded-lg border border-slate-900/10 bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#123f3d] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0d2f35]">Search tickets</button>
        </form>
    </div>

    <div class="grid gap-4">
        @forelse($tickets as $ticket)
            <a href="{{ route('customer.support.show', $ticket) }}" class="block rounded-xl border border-slate-900/10 bg-white p-5 shadow-sm transition hover:border-[#0d2f35]/30 hover:shadow-md">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $ticket->ticket_number }} · {{ $ticket->team?->name }}</div>
                        <h2 class="mt-1 truncate text-lg font-semibold text-slate-950">{{ $ticket->subject }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Last activity {{ $ticket->last_activity_at?->diffForHumans() }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str($ticket->status)->replace('_', ' ')->headline() }}</span>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ ucfirst($ticket->priority) }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-slate-900/10 bg-white p-10 text-center shadow-sm">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#f6f1e8] text-[#0d2f35]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                @if(request()->filled('search'))
                    <h2 class="text-xl font-semibold text-slate-950">No tickets match your search</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Try another ticket number, subject, or service.</p>
                    <a href="{{ route('customer.support.index', ['view' => $ticketView]) }}" class="mt-5 inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0d2f35]">Clear search</a>
                @elseif($ticketView === 'closed')
                    <h2 class="text-xl font-semibold text-slate-950">No closed tickets yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Tickets you close will appear here.</p>
                    <a href="{{ route('customer.support.index', ['view' => 'active']) }}" class="mt-5 inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0d2f35]">View active tickets</a>
                @else
                    <h2 class="text-xl font-semibold text-slate-950">No active tickets yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Create a ticket when you need help with service, billing, or network access.</p>
                    <a href="{{ route('customer.support.create') }}" class="mt-5 inline-flex rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0d2f35]">Create ticket</a>
                @endif
            </div>
        @endforelse
    </div>

    {{ $tickets->links() }}
</div>
@endsection
