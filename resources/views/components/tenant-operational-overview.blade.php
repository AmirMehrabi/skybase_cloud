@props(['overview'])
<section class="mb-7" aria-label="Operational overview">
<div class="mb-4 flex flex-wrap items-end justify-between gap-2"><div><h2 class="text-xl font-bold">Operational overview</h2><p class="mt-1 text-sm text-slate-600">Current records, excluding deleted customers and subscriptions.</p></div><p class="text-xs text-slate-500">Collected {{ $overview['collected_at']->format('Y-m-d H:i T') }}</p></div>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
@foreach(['customers' => 'Customers', 'subscriptions' => 'Subscriptions', 'online_subscriptions' => 'Subscriptions last recorded online', 'routers' => 'Routers', 'staff' => 'Staff accounts', 'open_tickets' => 'Open support tickets'] as $key => $label)
<div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-600">{{ $label }}</p><p class="mt-2 text-3xl font-bold text-[#0d2f35]">{{ number_format($overview[$key]) }}</p></div>
@endforeach
</div>
<div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
@foreach(['customer_statuses' => 'Customer states', 'subscription_statuses' => 'Subscription states', 'connection_statuses' => 'Recorded connection states', 'router_statuses' => 'Router states', 'staff_statuses' => 'Staff access', 'ticket_statuses' => 'Ticket states'] as $key => $label)
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">{{ $label }}</h3><dl class="space-y-2">
@forelse($overview[$key] as $state => $count)<div class="flex justify-between gap-4 text-sm"><dt class="text-slate-600">{{ str($state)->replace('_', ' ')->headline() }}</dt><dd class="font-semibold">{{ number_format($count) }}</dd></div>@empty<p class="text-sm text-slate-500">No records yet.</p>@endforelse
</dl></section>
@endforeach
</div>
<div class="mt-5 grid gap-4 lg:grid-cols-2">
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Growth and infrastructure</h3><dl class="grid grid-cols-2 gap-4 text-sm">
@foreach(['new_customers' => 'Customers added in 30 days', 'new_subscriptions' => 'Subscriptions added in 30 days', 'sites' => 'Sites', 'organizations' => 'Organizations', 'plans' => 'ISP service plans'] as $key => $label)<div><dt class="text-slate-600">{{ $label }}</dt><dd class="mt-1 font-semibold">{{ number_format($overview[$key]) }}</dd></div>@endforeach
<div><dt class="text-slate-600">Last staff login</dt><dd class="mt-1 font-semibold">{{ $overview['last_login'] ?? 'Unknown' }}</dd></div>
</dl><p class="mt-4 text-xs text-slate-500">Latest router status check: {{ $overview['router_checked_at'] ?? 'Not recorded' }}. Connection and router states reflect the last collected monitoring data.</p></section>
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">ISP subscriber billing</h3><p class="mb-4 text-sm text-slate-600">Amounts the ISP is collecting from its subscribers.</p><dl class="space-y-3 text-sm"><div class="flex justify-between"><dt>Outstanding invoices</dt><dd class="font-semibold">{{ number_format($overview['outstanding_invoices']) }}</dd></div><div class="flex justify-between"><dt>Overdue invoices</dt><dd class="font-semibold">{{ number_format($overview['overdue_invoices']) }}</dd></div><div class="flex justify-between"><dt>Outstanding balance</dt><dd class="font-semibold">{{ $overview['currency'] }} {{ number_format((float) $overview['outstanding_balance'], 2) }}</dd></div></dl></section>
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Latest subscriptions</h3>@forelse($overview['recent_subscriptions'] as $subscription)<div class="border-b border-slate-100 py-3 text-sm"><p class="font-medium">{{ $subscription->name ?: 'Subscription #'.$subscription->id }}</p><p class="mt-1 text-slate-500">{{ ucfirst($subscription->status) }} · {{ $subscription->service_type }} · {{ $subscription->created_at->format('Y-m-d') }}</p></div>@empty<p class="text-sm text-slate-500">No subscriptions yet.</p>@endforelse</section>
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Latest support tickets</h3>@forelse($overview['recent_tickets'] as $ticket)<div class="border-b border-slate-100 py-3 text-sm"><p class="font-medium">{{ $ticket->subject }}</p><p class="mt-1 text-slate-500">{{ str($ticket->status)->replace('_', ' ')->headline() }} · {{ ucfirst($ticket->priority) }} · {{ $ticket->created_at->format('Y-m-d') }}</p></div>@empty<p class="text-sm text-slate-500">No support tickets yet.</p>@endforelse</section>
</div></section>
