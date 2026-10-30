@extends('layouts.console')
@section('title', 'SkyBase owner console')
@section('content')

<h1 class="mb-2 text-2xl font-bold">ISP accounts</h1>
<p class="mb-6 text-gray-600">{{ $tenants->total() }} matching accounts. Search ISP accounts, review account activity and manage access.</p>
<p class="mb-5 text-sm text-gray-600">Active: {{ $totals['active'] }} · Suspended: {{ $totals['suspended'] }} · Archived: {{ $totals['archived'] }}</p>
<form method="GET" class="mb-6 grid gap-4 rounded-lg border border-gray-200 bg-white p-5 md:grid-cols-4">
<x-ui.input.text name="search" label="ISP name" :value="request('search')" />
<x-ui.input.select name="status" label="Application access" :value="request('status')" :options="['active'=>'Active','pending'=>'Pending','suspended'=>'Suspended']" placeholder="All access states" />
<x-ui.input.select name="archived" label="Archived accounts" :value="request('archived')" :options="['yes'=>'Archived only']" placeholder="Current accounts" />
<x-ui.input.select name="commercial" label="Commercial state" :value="request('commercial')" :options="['unconfigured'=>'Unconfigured','active'=>'Active terms','trial'=>'Trial','expiring_trial'=>'Trial expires within 7 days','expired'=>'Paid term expired']" placeholder="All commercial states" />
<x-ui.input.select name="activity" label="Staff activity" :value="request('activity')" :options="['inactive'=>'No login in 30 days / unknown']" placeholder="All activity" />
<button class="rounded-lg bg-teal-800 px-4 py-2 text-white">Filter</button>
</form>
<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white"><table class="w-full text-left text-sm"><thead><tr><th class="p-4">ISP</th><th>Access</th><th>SkyBase terms</th><th>Customers / routers / staff</th><th>Last staff login</th></tr></thead><tbody>
@forelse($tenants as $tenant)
@php($summary = $summaries[$tenant->id])
<tr class="border-t border-gray-200"><td class="p-4"><a class="font-semibold text-teal-700" href="{{ route('admin.super-admin.tenants.show', $tenant) }}">{{ $tenant->company_name ?: $tenant->name }}</a></td><td>{{ $tenant->archived_at ? 'Archived' : ($tenant->application_access_suspended ? 'Suspended' : ucfirst($tenant->status)) }}</td><td>{{ $summary['account']?->package_key ?? 'Unconfigured' }} / {{ $summary['account']?->status ?? 'Unconfigured' }}</td><td>{{ $summary['customers'] }} / {{ $summary['routers'] }} / {{ $summary['staff'] }}</td><td>{{ $summary['last_login'] ?? 'Unknown' }}</td></tr>
@empty<tr><td class="p-4" colspan="5">No matching accounts.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-5">{{ $tenants->links() }}</div>
@endsection
