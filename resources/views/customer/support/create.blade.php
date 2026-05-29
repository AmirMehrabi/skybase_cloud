@extends('layouts.customer')

@section('title', 'New Support Ticket')
@section('page_title', 'New Support Ticket')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-950">New Support Ticket</h1>
        <p class="text-sm text-slate-600">Choose the best team so your request reaches the right queue.</p>
    </div>

    <form method="POST" action="{{ route('customer.support.store') }}" enctype="multipart/form-data" class="rounded-xl border border-slate-900/10 bg-white p-6 shadow-sm">
        @csrf
        <x-form.validation-summary />

        <div class="grid gap-4 md:grid-cols-2">
            <x-input.select id="ticket_team_id" name="ticket_team_id" label="Category" :options="$teams->pluck('name', 'id')" placeholder="Select category" required />
            <x-input.select id="priority" name="priority" label="Priority" :value="old('priority', 'normal')" :options="['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent']" required />
        </div>
        <x-input.select id="subscription_id" name="subscription_id" label="Related service" :options="$subscriptions->pluck('subscription_code', 'id')" placeholder="Optional" />
        <x-input.text id="subject" name="subject" label="Subject" required />
        <x-input.textarea id="message" name="message" label="What can we help with?" rows="7" required />

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700" for="attachments">Attachments</label>
            <input id="attachments" name="attachments[]" type="file" multiple class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-slate-500">Up to 5 files, 10 MB each.</p>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('customer.support.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</a>
            <button class="rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white">Submit ticket</button>
        </div>
    </form>
</div>
@endsection
