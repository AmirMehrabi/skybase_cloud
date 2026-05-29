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
                <h2 class="text-xl font-semibold text-slate-950">No support tickets yet</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Create a ticket when you need help with service, billing, or network access.</p>
                <a href="{{ route('customer.support.create') }}" class="mt-5 inline-flex rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white">Create ticket</a>
            </div>
        @endforelse
    </div>

    {{ $tickets->links() }}
</div>
@endsection
