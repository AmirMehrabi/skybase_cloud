@extends('layouts.customer')
@section('title', 'Payment status')
@section('page_title', 'Payment status')
@section('content')
<x-ui.card title="Payment status">
    <div class="space-y-5">
        <p class="text-sm text-slate-600">{{ match($attempt->status) { 'completed' => 'Payment received. Your invoice balance has been updated.', 'sandbox_completed' => 'Sandbox payment successful. Your real invoice balance has not changed.', 'review' => 'Payment received and awaiting reconciliation by your provider.', default => request()->boolean('cancelled') ? 'Checkout cancelled. You can return to your invoices and try again. Payment confirmation may still arrive if a payment was completed.' : 'Awaiting payment confirmation. Refresh this page shortly to check again.' } }}</p>
        <p class="text-xs text-slate-500">Reference: {{ $attempt->reference }}</p>
        <a class="inline-flex rounded-lg bg-teal-900 px-4 py-2 text-sm font-medium text-white" href="{{ route('customer.payments.status', $attempt->reference) }}">Refresh status</a>
        <a class="inline-flex rounded-lg bg-teal-900 px-4 py-2 text-sm font-medium text-white" href="{{ route('customer.invoices.index') }}">Back to invoices</a>
    </div>
</x-ui.card>
@endsection
