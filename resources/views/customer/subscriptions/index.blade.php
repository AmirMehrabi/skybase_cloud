@extends('layouts.customer')

@section('title', 'Subscriptions')
@section('page_title', 'Subscriptions')

@section('content')
<div class="rounded-xl border border-slate-900/10 bg-white shadow-sm">
    <div class="border-b border-slate-900/10 px-5 py-4">
        <h2 class="text-lg font-semibold text-slate-950">Your subscriptions</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-900/10">
            <thead class="bg-[#fbf7ed]">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Code</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Plan</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Connection</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Next billing</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-900/10">
                @forelse($subscriptions as $subscription)
                    <tr>
                        <td class="px-5 py-4 text-sm font-medium text-slate-950">{{ $subscription->subscription_code }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $subscription->plan?->name ?? 'No plan assigned' }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ strtoupper((string) $subscription->connection_type) }}</td>
                        <td class="px-5 py-4 text-sm"><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold capitalize text-emerald-700">{{ $subscription->status }}</span></td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $subscription->next_billing_date?->format('M d, Y') ?? 'Not scheduled' }}</td>
                        <td class="px-5 py-4 text-right">
                            <x-ui.action-icon
                                :href="route('customer.subscriptions.show', $subscription)"
                                icon="view"
                                :label="'View subscription '.$subscription->subscription_code"
                                class="inline-flex"
                            />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">No subscriptions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
