@extends('layouts.console')
@section('title', 'SkyBase owner console')
@section('content')

<h1 class="mb-2 text-2xl font-bold">ISP accounts</h1>
<p class="mb-6 text-gray-600">{{ $tenants->total() }} matching accounts. Search ISP accounts, review account activity and manage access.</p>
<div class="mb-7 grid gap-4 sm:grid-cols-3">
@foreach(['active' => 'Active ISP accounts', 'suspended' => 'Application access suspended', 'archived' => 'Archived accounts'] as $key => $label)
<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-600">{{ $label }}</p><p class="mt-2 text-3xl font-bold tracking-tight text-[#0d2f35]">{{ number_format($totals[$key]) }}</p></section>
@endforeach
</div>
<form method="GET" class="mb-6 grid gap-4 rounded-lg border border-gray-200 bg-white p-5 md:grid-cols-4">
<x-ui.input.text name="search" label="ISP name" :value="request('search')" />
<x-ui.input.select name="status" label="Application access" :value="request('status')" :options="['active'=>'Active','pending'=>'Pending','suspended'=>'Suspended']" placeholder="All access states" />
<x-ui.input.select name="archived" label="Archived accounts" :value="request('archived')" :options="['yes'=>'Archived only']" placeholder="Current accounts" />
<x-ui.input.select name="commercial" label="Commercial state" :value="request('commercial')" :options="['unconfigured'=>'Unconfigured','active'=>'Active terms','trial'=>'Trial','expiring_trial'=>'Trial expires within 7 days','expired'=>'Paid term expired']" placeholder="All commercial states" />
<x-ui.input.select name="activity" label="Staff activity" :value="request('activity')" :options="['inactive'=>'No login in 30 days / unknown']" placeholder="All activity" />
<button class="rounded-lg bg-teal-800 px-4 py-2 text-white">Apply filters</button>
<a href="{{ route('admin.super-admin.tenants.index') }}" class="self-center text-sm font-semibold text-teal-700">Clear filters</a>
</form>
<div x-data="{ selectedTenantIds: [], tenantIds: @js($tenants->getCollection()->modelKeys()) }">
<div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-4">
    <p class="text-sm text-slate-600"><span class="font-semibold text-slate-900" x-text="selectedTenantIds.length"></span> selected on this page</p>
    <form id="bulk-delete-tenants-form" method="POST" action="{{ route('admin.super-admin.tenants.bulk-destroy') }}" x-on:submit="if (!confirm('Delete ' + selectedTenantIds.length + ' selected tenants? Application access will stop. Records are retained for recovery.')) $event.preventDefault()">
        @csrf
        <x-ui.input.text name="confirmed" type="hidden" value="1" />
        <button type="submit" x-bind:disabled="selectedTenantIds.length === 0" x-text="'Delete selected (' + selectedTenantIds.length + ')'" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 disabled:cursor-not-allowed disabled:opacity-50"></button>
    </form>
</div>
@if($errors->has('tenant_ids') || $errors->has('tenant_ids.*'))<p class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $errors->first('tenant_ids') ?: $errors->first('tenant_ids.*') }}</p>@endif
<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white"><table class="w-full text-left text-sm"><thead><tr><th class="p-4"><input type="checkbox" aria-label="Select all tenants on this page" x-bind:checked="tenantIds.length > 0 && selectedTenantIds.length === tenantIds.length" x-on:change="selectedTenantIds = $event.target.checked ? tenantIds : []" class="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700"></th><th>ISP</th><th>Access</th><th>SkyBase terms</th><th>Customers</th><th>Subscriptions</th><th>Operations</th><th>Last staff login</th><th>Actions</th></tr></thead><tbody>
@forelse($tenants as $tenant)
@php($summary = $summaries[$tenant->id])
<tr class="border-t border-gray-200"><td class="p-4"><input type="checkbox" name="tenant_ids[]" value="{{ $tenant->id }}" form="bulk-delete-tenants-form" x-model="selectedTenantIds" aria-label="Select {{ $tenant->company_name ?: $tenant->name }}" class="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700"></td><td><a class="font-semibold text-teal-700" href="{{ route('admin.super-admin.tenants.show', $tenant) }}">{{ $tenant->company_name ?: $tenant->name }}</a></td><td>{{ $tenant->archived_at ? 'Archived' : ($tenant->application_access_suspended ? 'Suspended' : ucfirst($tenant->status)) }}</td><td>{{ $summary['account']?->package_key ?? 'Unconfigured' }} / {{ $summary['account']?->status ?? 'Unconfigured' }}</td><td><span class="text-lg font-bold">{{ number_format($summary['customers']) }}</span></td>
<td><span class="text-lg font-bold">{{ number_format($summary['subscriptions']) }}</span><p class="mt-1 whitespace-nowrap text-xs text-slate-500">{{ $summary['active_subscriptions'] }} active · {{ $summary['online_subscriptions'] }} last recorded online</p></td>
<td class="min-w-48"><p>{{ $summary['online_routers'] }} / {{ $summary['routers'] }} routers online</p><p class="mt-1 text-xs text-slate-500">{{ $summary['staff'] }} staff · {{ $summary['open_tickets'] }} open tickets</p><p class="mt-1 text-xs {{ $summary['overdue_invoices'] ? 'font-semibold text-amber-700' : 'text-slate-500' }}">{{ $summary['overdue_invoices'] }} overdue invoices</p></td><td>{{ $summary['last_login'] ?? 'Unknown' }}</td><td><div class="flex flex-col items-start gap-2"><a class="font-semibold text-teal-700" href="{{ route('admin.super-admin.tenants.show', $tenant) }}">View account</a><x-tenant-delete-button :tenant="$tenant" /></div></td></tr>
@empty<tr><td class="p-4" colspan="9">No matching accounts.</td></tr>@endforelse
</tbody></table></div>
</div>
<div class="mt-5">{{ $tenants->links() }}</div>
@endsection
