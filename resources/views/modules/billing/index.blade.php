@extends('layouts.admin')
@section('title', 'SkyBase subscription')
@section('content')
<x-owner-navigation />
<h1 class="mb-3 text-2xl font-bold">Your SkyBase subscription</h1>
<p class="mb-3">Package: {{ $account?->package_key ?? 'Unconfigured' }} · Status: {{ $account?->status ?? 'Unconfigured' }} · Paid through: {{ $account?->paid_through?->toDateString() ?? 'None' }}</p>
<p class="mb-6 text-sm text-gray-600">Monthly USD billing. Payments are manual. Renewals add one calendar month; package changes are available after the paid term. No proration or automatic charges. Cancellation preserves the current term. Expiry has no automatic effect on subscriber connectivity.</p>
@if(config('skybase_billing.dummy_gateway_enabled'))<p class="mb-5 rounded-lg bg-amber-50 p-4 text-amber-900">Demo checkout: all payments are simulated. No money is collected.</p>@endif
<div class="mb-8 grid gap-4 md:grid-cols-3">
@foreach($packages as $key => $package)
<section class="rounded-lg border border-gray-200 bg-white p-5"><h2 class="text-lg font-semibold">{{ $package['name'] }}</h2><p class="my-2">USD {{ number_format($package['monthly_price_minor'] / 100, 2) }} / month</p><p>Up to {{ number_format($package['subscriber_limit']) }} subscribers</p>
@if($package['self_service'])<form method="POST" action="{{ route('skybase.billing.checkout') }}" class="mt-4">@csrf<x-ui.input.text name="package_key" type="hidden" :value="$key" /><button class="rounded-lg bg-teal-800 px-4 py-2 text-white">{{ $key === 'free' ? 'Select Free' : 'Start simulated checkout' }}</button></form>
@else<p class="mt-4">Contact sales for agreed Enterprise terms.</p>@endif
</section>@endforeach</div>
<form method="POST" action="{{ route('skybase.billing.cancel') }}" class="mb-6">@csrf<button class="rounded-lg border border-gray-300 px-4 py-2">{{ $account?->cancel_at_period_end ? 'Renewal cancellation recorded' : 'Cancel renewal' }}</button></form>
<h2 class="mb-3 text-lg font-semibold">Software invoices</h2><x-billing-invoices :invoices="$invoices" />{{ $invoices->links() }}
@endsection
