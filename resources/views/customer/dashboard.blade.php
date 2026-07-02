@extends('layouts.customer')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <section class="overflow-hidden rounded-2xl bg-[#0d2f35] p-6 text-white shadow-[0_24px_60px_rgba(13,47,53,0.18)] sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-100/75">Welcome back</p>
                <h2 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $customer->full_name }}</h2>
                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-teal-50/80">
                    <span>Customer {{ $customer->customer_code }}</span>
                    <span>{{ $customer->email }}</span>
                    <span class="capitalize">{{ $customer->status }} account</span>
                </div>
            </div>
            <a href="{{ route('customer.profile.show') }}" class="inline-flex w-fit items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-[#0d2f35] transition hover:bg-emerald-50">
                Manage profile
            </a>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Active subscriptions</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['active_subscriptions'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['total_subscriptions'] }} total</p>
        </div>
        <div class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Online now</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">{{ $stats['online_subscriptions'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $stats['suspended_subscriptions'] }} suspended</p>
        </div>
        <div class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Unpaid invoices</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['unpaid_invoices'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Balance {{ number_format((float) $stats['current_balance'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Open tickets</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['open_tickets'] }}</p>
            <a href="{{ route('customer.support.index') }}" class="mt-1 inline-block text-xs font-semibold text-teal-700 hover:underline">View support</a>
        </div>
    </section>

    @include('customer.partials.bandwidth-chart', [
        'endpoint' => route('customer.dashboard.usage'),
        'title' => 'Total subscription usage',
        'description' => 'Combined download and upload throughput across all your subscriptions.',
    ])

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm xl:col-span-2 sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-950">Recent subscriptions</h3>
                <a href="{{ route('customer.subscriptions.index') }}" class="text-sm font-semibold text-teal-700 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-slate-900/10">
                @forelse($subscriptions as $subscription)
                    <a href="{{ route('customer.subscriptions.show', $subscription) }}" class="flex items-center justify-between gap-4 py-3 transition hover:bg-[#fbf7ed]">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-950">{{ $subscription->subscription_code }}</p>
                            <p class="truncate text-sm text-slate-500">{{ $subscription->plan?->name ?? 'No plan assigned' }}</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold capitalize text-emerald-700">{{ $subscription->status }}</span>
                    </a>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No subscriptions yet.</p>
                @endforelse
            </div>
        </section>

        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Account information</h3>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-slate-500">Phone</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $customer->mobile ?: ($customer->phone ?: 'Not provided') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Location</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ collect([$customer->city, $customer->country])->filter()->join(', ') ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Next billing</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $nextBillingDate ? \Illuminate\Support\Carbon::parse($nextBillingDate)->format('M d, Y') : 'Not scheduled' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-950">Recent invoices</h3>
                    <a href="{{ route('customer.invoices.index') }}" class="text-xs font-semibold text-teal-700 hover:underline">View all</a>
                </div>
                <div class="mt-3 divide-y divide-slate-900/10">
                    @forelse($invoices->take(3) as $invoice)
                        <div class="flex items-center justify-between gap-3 py-3 text-sm">
                            <span class="font-medium text-slate-800">{{ $invoice->invoice_number }}</span>
                            <span class="capitalize text-slate-500">{{ $invoice->status }}</span>
                        </div>
                    @empty
                        <p class="py-5 text-sm text-slate-500">No invoices yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
