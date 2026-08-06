@extends('layouts.customer')

@section('title', $subscription->subscription_code)
@section('page_title', 'Subscription details')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('customer.subscriptions.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-teal-700 hover:underline">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to subscriptions
            </a>
            <h2 class="mt-2 text-2xl font-bold text-slate-950">{{ $subscription->name ?: $subscription->subscription_code }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $subscription->subscription_code }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($subscription->isPppoe())
                <span class="rounded-full px-3 py-1.5 text-xs font-semibold capitalize {{ $subscription->connection_status === 'online' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                    {{ $subscription->connection_status ?: 'Unknown' }}
                </span>
            @endif
            <span class="rounded-full bg-teal-50 px-3 py-1.5 text-xs font-semibold capitalize text-teal-700">{{ $subscription->status }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-900/10 bg-white p-6 shadow-sm xl:col-span-2">
            <h3 class="text-lg font-semibold text-slate-950">Service information</h3>
            <dl class="mt-5 grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Plan</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $subscription->plan?->name ?? 'No plan assigned' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Service type</dt>
                    <dd class="mt-1 font-semibold capitalize text-slate-900">{{ str_replace('_', ' ', $subscription->service_type) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Connection</dt>
                    <dd class="mt-1 font-semibold uppercase text-slate-900">{{ $subscription->connection_type }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Site</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $subscription->site ?: 'Not assigned' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Plan speed</dt>
                    <dd class="mt-1 font-semibold text-slate-900">
                        @if($subscription->plan && filled($subscription->plan->download_speed) && filled($subscription->plan->upload_speed) && filled($subscription->plan->bandwidth_unit))
                            {{ $subscription->plan->download_speed }} {{ $subscription->plan->bandwidth_unit }} download / {{ $subscription->plan->upload_speed }} {{ $subscription->plan->bandwidth_unit }} upload
                        @else
                            Not specified
                        @endif
                    </dd>
                </div>
                @if($subscription->isPppoe())
                    <div>
                        <dt class="text-sm text-slate-500">PPPoE username</dt>
                        <dd class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $subscription->pppoe_username ?: 'Not assigned' }}</dd>
                    </div>
                @endif
                @if($subscription->accessPoint)
                    <div>
                        <dt class="text-sm text-slate-500">Access point</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $subscription->accessPoint->name }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-sm text-slate-500">Last status check</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $subscription->connection_status_checked_at?->format('M d, Y H:i') ?? 'Not checked yet' }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-900/10 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Billing</h3>
            <dl class="mt-5 space-y-5">
                <div>
                    <dt class="text-sm text-slate-500">Recurring price</dt>
                    <dd class="mt-1 text-xl font-bold text-slate-950">${{ number_format((float) $subscription->total_price, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Billing cycle</dt>
                    <dd class="mt-1 font-semibold capitalize text-slate-900">{{ $subscription->billing_cycle }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Next billing</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $subscription->next_billing_date?->format('M d, Y') ?? 'Not scheduled' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Service started</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $subscription->start_date?->format('M d, Y') ?? 'Not recorded' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Activated</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $subscription->activation_date?->format('M d, Y') ?? 'Not recorded' }}</dd>
                </div>
                @if($subscription->end_date)
                    <div>
                        <dt class="text-sm text-slate-500">Ends</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $subscription->end_date->format('M d, Y') }}</dd>
                    </div>
                @endif
            </dl>
        </section>
    </div>

    @include('customer.partials.bandwidth-chart', [
        'endpoint' => route('customer.subscriptions.bandwidth.history', $subscription),
        'title' => 'Subscription usage',
        'description' => 'Download and upload throughput for this subscription.',
    ])

    <section class="rounded-2xl border border-slate-900/10 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-950">Recent invoices</h3>
                <p class="mt-1 text-sm text-slate-500">Latest billing activity for this subscription.</p>
            </div>
            <a href="{{ route('customer.invoices.index') }}" class="text-sm font-semibold text-teal-700 hover:underline">All invoices</a>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-900/10">
                <thead>
                    <tr>
                        <th class="py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Invoice</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Issued</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                        <th class="py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-900/10">
                    @forelse($subscription->invoices as $invoice)
                        <tr>
                            <td class="py-3 text-sm font-medium text-slate-900">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $invoice->issue_date?->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">${{ number_format((float) $invoice->total, 2) }}</td>
                            <td class="py-3 text-right text-sm capitalize text-slate-600">{{ $invoice->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-sm text-slate-500">No invoices for this subscription.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
