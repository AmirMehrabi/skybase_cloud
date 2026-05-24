@extends('layouts.customer')

@section('title', 'Support')
@section('page_title', 'Support')

@section('content')
<div class="rounded-xl border border-slate-900/10 bg-white p-8 text-center shadow-sm">
    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#f6f1e8] text-[#0d2f35]">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
    </div>
    <h2 class="text-xl font-semibold text-slate-950">Support tickets are coming soon</h2>
    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">This portal section is reserved for customer support requests. Ticket creation and history will be added when the ticket model is implemented.</p>
</div>
@endsection
