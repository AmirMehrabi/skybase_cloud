@extends('layouts.customer')

@section('title', 'Invoices')
@section('page_title', 'Invoices')

@section('content')
<div class="rounded-xl border border-slate-900/10 bg-white shadow-sm">
    <div class="border-b border-slate-900/10 px-5 py-4">
        <h2 class="text-lg font-semibold text-slate-950">Your invoices</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-900/10">
            <thead class="bg-[#fbf7ed]">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Invoice</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Subscription</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Issue date</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Due date</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-900/10">
                @forelse($invoices as $invoice)
                    <tr>
                        <td class="px-5 py-4 text-sm font-medium text-slate-950">{{ $invoice->invoice_number }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $invoice->subscription?->subscription_code ?? 'N/A' }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $invoice->issue_date?->format('M d, Y') }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $invoice->due_date?->format('M d, Y') }}</td>
                        <td class="px-5 py-4 text-sm font-semibold text-slate-950">{{ number_format((float) $invoice->total, 2) }}</td>
                        <td class="px-5 py-4 text-sm"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{{ str_replace('_', ' ', $invoice->status) }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">No invoices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
