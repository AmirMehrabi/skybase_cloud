@extends('layouts.admin')
@section('title', 'Simulated payment')
@section('content')
<x-owner-navigation />
<section class="mx-auto max-w-xl rounded-lg border border-gray-200 bg-white p-6"><h1 class="mb-4 text-2xl font-bold">Dummy payment — no money collected</h1>
<p>Invoice #{{ $invoice->id }} · {{ ucfirst($invoice->package_key) }}</p><p class="my-3 text-xl">{{ $invoice->currency }} {{ number_format($invoice->amount_minor / 100, 2) }}</p>
<p>Payment: {{ $attempt->status }} · Invoice: {{ $invoice->status }}</p><p class="mb-5 text-sm text-gray-500">Expires {{ $attempt->expires_at }}. Successful settlement grants simulated software access.</p>
@if($attempt->status === 'pending' && $invoice->status === 'pending' && $attempt->expires_at->isFuture())
<form method="POST" action="{{ route('skybase.billing.settle', $attempt->authority) }}" class="space-y-4">@csrf
<x-ui.input.select name="outcome" label="Simulate outcome" value="success" :options="['success'=>'Success (simulated)','cancelled'=>'Cancel','failed'=>'Failure']" required />
<button class="rounded-lg bg-teal-800 px-5 py-2 text-white">Submit simulation</button></form>
@endif
<a class="mt-5 inline-block text-teal-700" href="{{ route('skybase.billing.index') }}">Back to billing / retry</a></section>
@endsection
