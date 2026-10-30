@extends('layouts.console')
@section('title', 'ISP account')
@section('content')

<a href="{{ route('admin.super-admin.tenants.index') }}" class="mb-5 inline-flex text-sm font-semibold text-teal-700">← All ISP accounts</a>
<h1 class="mb-2 text-2xl font-bold">{{ $tenant->company_name }}</h1>
<p class="mb-6 text-gray-600">{{ $tenant->email }} · {{ $tenant->application_access_suspended ? 'Application suspended' : $tenant->status }} {{ $tenant->archived_at ? ' / Archived' : '' }} · {{ $overview['customers'] }} customers · {{ $overview['subscriptions'] }} subscriptions · {{ $overview['routers'] }} routers</p>
<div class="mb-7 flex justify-end"><x-tenant-delete-button :tenant="$tenant" /></div>
<section class="mb-7 rounded-lg border border-gray-200 bg-white p-5">
    <div class="mb-4">
        <h2 class="text-lg font-semibold">Tenant owner</h2>
        <p class="mt-1 text-sm text-gray-600">Personal contact and account details for the ISP owner.</p>
    </div>
    @if($owner)
    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Name</dt><dd class="mt-1 font-medium text-gray-900">{{ $owner->name }}</dd></div>
        <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Owner email</dt><dd class="mt-1"><a class="font-medium text-teal-700 hover:underline" href="mailto:{{ $owner->email }}">{{ $owner->email }}</a></dd></div>
        <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Signup phone</dt><dd class="mt-1 font-medium text-gray-900">{{ $tenant->phone ?: 'Not provided' }}</dd></div>
        <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Country</dt><dd class="mt-1 font-medium text-gray-900">{{ $tenant->country ?: 'Not provided' }}</dd></div>
        <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Email verification</dt><dd class="mt-1 font-medium text-gray-900">{{ $owner->email_verified_at ? 'Verified' : 'Not verified' }}</dd></div>
        <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Owner account created</dt><dd class="mt-1 font-medium text-gray-900">{{ $owner->created_at?->format('M j, Y') ?? 'Unknown' }}</dd></div>
        <div><dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Last sign in</dt><dd class="mt-1 font-medium text-gray-900">{{ $owner->last_login_at?->format('M j, Y g:i A') ?? 'No sign-in recorded' }}</dd></div>
    </dl>
    @else
    <p class="text-sm text-gray-600">No user with the tenant owner role was found.</p>
    @endif
</section>
<x-tenant-operational-overview :overview="$overview" />
<div class="grid gap-6 lg:grid-cols-2">
<section class="rounded-lg border border-gray-200 bg-white p-5"><h2 class="mb-3 text-lg font-semibold">SkyBase commercial terms</h2>
<p>{{ $account?->package_key ?? 'Unconfigured' }} / {{ $account?->status ?? 'Unconfigured' }}</p>
<p>Monthly price: {{ $account?->currency ?? 'USD' }} {{ $account?->monthly_price_minor !== null ? number_format($account->monthly_price_minor / 100, 2) : 'Unknown' }}</p>
<p>Subscriber allowance: {{ $account?->subscriber_limit ?? 'Unconfigured' }}</p>
<p>Trial ends: {{ $account?->trial_ends_at?->toDateString() ?? 'None' }} · Paid through: {{ $account?->paid_through?->toDateString() ?? 'None' }}</p>
<p class="mt-3 text-sm text-gray-600">Recorded entitlements: {{ implode(', ', $account?->features ?? []) }}</p>
</section>
<section class="rounded-lg border border-gray-200 bg-white p-5" x-data="{ action: @js(old('action', 'note')) }"><h2 class="mb-3 text-lg font-semibold">Manage account</h2>
<p class="mb-4 text-sm text-gray-600">Package assignment records agreed terms; it does not record payment. Suspension blocks application access and does not disconnect subscribers.</p>
<form method="POST" action="{{ route('admin.super-admin.tenants.update', $tenant) }}" class="space-y-4" x-on:submit="if (['suspend', 'archive'].includes(action) &amp;&amp; !confirm('This will block application access for this ISP. Continue?')) $event.preventDefault()">@csrf @method('PUT')
<x-ui.input.select name="action" label="Action" x-model="action" value="note" :options="['note'=>'Internal note','contacts'=>'Edit contacts','package'=>'Assign package','trial'=>'Extend trial','limit'=>'Override subscriber allowance','suspend'=>'Suspend application access','activate'=>'Activate / restore','archive'=>'Archive account']" required />
<div x-cloak x-show="action === 'contacts'" class="space-y-3">
<x-ui.input.text name="name" label="Account name" :value="old('name', $tenant->name)" />
<x-ui.input.text name="company_name" label="ISP company" :value="old('company_name', $tenant->company_name)" />
<x-ui.input.text name="email" type="email" label="Email" :value="old('email', $tenant->email)" />
<x-ui.input.text name="phone" label="Phone" :value="old('phone', $tenant->phone)" />
</div>
<div x-cloak x-show="action === 'package'"><x-ui.input.select name="package_key" label="Package" :options="collect($packages)->mapWithKeys(fn ($package, $key) => [$key => $package['name']])->all()" /></div>
<div x-cloak x-show="action === 'trial'"><x-ui.input.text name="trial_ends_at" type="date" label="Trial end" /></div>
<div x-cloak x-show="action === 'limit'"><x-ui.input.text name="subscriber_limit" type="number" label="Subscriber allowance" min="0" /></div>
<x-ui.input.textarea name="reason" label="Reason / internal note" :value="old('reason')" required />
<button class="rounded-lg bg-teal-800 px-5 py-2 text-white">Save action</button>
</form></section>
<section class="rounded-lg border border-gray-200 bg-white p-5"><h2 class="mb-3 text-lg font-semibold">Staff (up to 20 recent accounts)</h2>
@forelse($staff as $user)<p class="mb-3">{{ $user->name }} · {{ $user->role }} · {{ $user->email }}<br><span class="text-sm text-gray-500">Last login: {{ $user->last_login_at ?? 'Unknown' }}</span></p>@empty<p>No staff.</p>@endforelse
</section>
<section class="rounded-lg border border-gray-200 bg-white p-5"><h2 class="mb-3 text-lg font-semibold">Internal notes</h2>
@forelse($notes as $note)<p class="mb-3 whitespace-pre-line">{{ $note->body }}<br><span class="text-xs text-gray-500">{{ $note->created_at }} · Administrator #{{ $note->actor_id }}</span></p>@empty<p>No notes.</p>@endforelse
</section>
<section class="rounded-lg border border-gray-200 bg-white p-5 lg:col-span-2"><h2 class="mb-3 text-lg font-semibold">Software invoices</h2><x-billing-invoices :invoices="$invoices" /></section>
<section class="rounded-lg border border-gray-200 bg-white p-5 lg:col-span-2"><h2 class="mb-3 text-lg font-semibold">Administrative history (latest 50)</h2>
@forelse($events as $event)<article class="border-b border-gray-100 py-3"><p class="font-medium">{{ $event->event }} · {{ $event->created_at }} · Actor #{{ $event->actor_id }}</p><p>{{ $event->reason }}</p>@if($event->changes)<details class="mt-2 text-sm"><summary class="cursor-pointer text-teal-700">View recorded changes</summary><pre class="mt-2 overflow-x-auto whitespace-pre-wrap rounded bg-gray-50 p-3">{{ json_encode($event->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>@endif</article>@empty<p>No recorded actions.</p>@endforelse
</section></div>
@endsection
