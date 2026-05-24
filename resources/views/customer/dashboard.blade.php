@extends('layouts.customer')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-sm font-medium text-slate-500">Welcome back</p>
        <h2 class="text-2xl font-bold text-slate-950">{{ $customer->full_name }}</h2>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-900/10 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Active subscriptions</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['active_subscriptions'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-900/10 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Unpaid invoices</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['unpaid_invoices'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-900/10 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Open tickets</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['open_tickets'] }}</p>
        </div>
        <div class="rounded-xl border border-slate-900/10 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Current balance</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ number_format((float) $stats['current_balance'], 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-900/10 bg-white p-5 shadow-sm xl:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-950">Recent subscriptions</h3>
                <a href="{{ route('customer.subscriptions.index') }}" class="text-sm font-medium text-[#0d2f35] hover:underline">View all</a>
            </div>
            <div class="divide-y divide-slate-900/10">
                @forelse($subscriptions as $subscription)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <p class="font-medium text-slate-950">{{ $subscription->subscription_code }}</p>
                            <p class="text-sm text-slate-500">{{ $subscription->plan?->name ?? 'No plan assigned' }}</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold capitalize text-emerald-700">{{ $subscription->status }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No subscriptions yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-slate-900/10 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-slate-950">Network snapshot</h3>
            <div class="space-y-3">
                @foreach($usage as $item)
                    <div class="rounded-lg bg-[#f6f1e8] p-4">
                        <p class="text-sm text-slate-500">{{ $item['label'] }}</p>
                        <div class="mt-1 flex items-end justify-between">
                            <p class="text-xl font-bold text-slate-950">{{ $item['value'] }}</p>
                            <p class="text-xs font-semibold text-emerald-700">{{ $item['change'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
