@extends('layouts.customer')

@section('title', $ticket->ticket_number)
@section('page_title', $ticket->ticket_number)

@section('content')
<div class="grid gap-6 xl:grid-cols-[1fr_320px]">
    <section class="space-y-6">
        <div class="rounded-xl border border-slate-900/10 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-500">{{ $ticket->team?->name }}</div>
                    <h1 class="mt-1 text-2xl font-bold text-slate-950">{{ $ticket->subject }}</h1>
                    <p class="mt-2 text-sm text-slate-600">{{ $ticket->subscription?->subscription_code ? 'Service '.$ticket->subscription->subscription_code : 'No related service selected' }}</p>
                </div>
                <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ str($ticket->status)->replace('_', ' ')->headline() }}</span>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($messages as $message)
                <article class="rounded-xl border border-slate-900/10 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">{{ $message->authorName() }}</div>
                            <div class="text-xs text-slate-500">{{ $message->created_at?->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>
                    <div class="whitespace-pre-line text-sm leading-6 text-slate-800">{{ $message->body }}</div>
                    @if($message->attachments->where('visibility', 'public')->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($message->attachments->where('visibility', 'public') as $attachment)
                                <a href="{{ route('customer.support.attachments.download', [$ticket, $attachment]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ $attachment->downloadName() }}</a>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        @unless($ticket->isClosed())
            <form method="POST" action="{{ route('customer.support.reply', $ticket) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-900/10 bg-white p-6 shadow-sm">
                @csrf
                <h2 class="mb-4 text-lg font-semibold text-slate-950">Add reply</h2>
                <x-input.textarea id="body" name="body" label="Message" rows="6" required />
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700" for="attachments">Attachments</label>
                    <input id="attachments" name="attachments[]" type="file" multiple class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <button class="rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white">Send reply</button>
            </form>
        @endunless
    </section>

    <aside class="space-y-4">
        <div class="rounded-xl border border-slate-900/10 bg-white p-5 text-sm shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-950">Ticket details</h2>
            <dl class="space-y-3">
                <div><dt class="text-slate-500">Status</dt><dd class="font-medium text-slate-900">{{ str($ticket->status)->replace('_', ' ')->headline() }}</dd></div>
                <div><dt class="text-slate-500">Priority</dt><dd class="font-medium text-slate-900">{{ ucfirst($ticket->priority) }}</dd></div>
                <div><dt class="text-slate-500">Created</dt><dd class="font-medium text-slate-900">{{ $ticket->created_at?->format('Y-m-d H:i') }}</dd></div>
                <div><dt class="text-slate-500">Last activity</dt><dd class="font-medium text-slate-900">{{ $ticket->last_activity_at?->diffForHumans() }}</dd></div>
            </dl>
            @unless($ticket->isClosed())
                <form method="POST" action="{{ route('customer.support.close', $ticket) }}" class="mt-5">
                    @csrf
                    <button class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Close ticket</button>
                </form>
            @endunless
        </div>
    </aside>
</div>
@endsection
