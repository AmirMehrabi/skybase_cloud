@extends('layouts.admin')

@section('title', 'All IP Addresses')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between"><div><h1 class="text-2xl font-bold text-gray-900">All IP Addresses</h1><p class="mt-1 text-sm text-gray-500">Live IPAM inventory across all pools</p></div><a href="{{ route('ipam.dashboard') }}" class="text-sm font-medium text-blue-600">Dashboard</a></div>
    <form method="GET" class="grid grid-cols-1 gap-4 rounded-2xl border border-gray-200 bg-white p-4 md:grid-cols-6">
        <select name="pool_id" class="rounded-lg border-gray-300 text-sm"><option value="">All pools</option>@foreach($pools as $pool)<option value="{{ $pool->id }}" @selected(request('pool_id') == $pool->id)>{{ $pool->name }}</option>@endforeach</select>
        <select name="router_id" class="rounded-lg border-gray-300 text-sm"><option value="">All routers</option>@foreach($routers as $router)<option value="{{ $router->id }}" @selected(request('router_id') == $router->id)>{{ $router->name }}</option>@endforeach</select>
        <select name="status" class="rounded-lg border-gray-300 text-sm"><option value="">All statuses</option>@foreach(['available', 'assigned', 'reserved', 'blocked'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
        <input name="customer" value="{{ request('customer') }}" placeholder="Customer" class="rounded-lg border-gray-300 text-sm"><input name="ip" value="{{ request('ip') }}" placeholder="Search IP" class="rounded-lg border-gray-300 text-sm font-mono">
        <div class="flex gap-2"><button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white">Filter</button><a href="{{ route('ipam.ips.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Clear</a></div>
    </form>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">@foreach(['Total' => $statistics->total, 'Assigned' => $statistics->assigned, 'Available' => $statistics->available, 'Reserved' => $statistics->reserved] as $label => $value)<div class="rounded-xl border border-gray-200 bg-white p-4"><p class="text-xs text-gray-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($value ?? 0) }}</p></div>@endforeach</div>
    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">IP</th><th class="px-4 py-3">Pool</th><th class="px-4 py-3">Router</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Subscription</th><th class="px-4 py-3">Assigned</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($ipAddresses as $ip)<tr><td class="px-4 py-3 font-mono text-blue-700">{{ $ip->ip_address }}</td><td class="px-4 py-3"><a class="text-blue-600" href="{{ route('ipam.pools.show', $ip->ipPool) }}">{{ $ip->ipPool->name }}</a></td><td class="px-4 py-3">{{ $ip->ipPool->router?->name ?? '-' }}</td><td class="px-4 py-3">{{ ucfirst($ip->status) }}</td><td class="px-4 py-3">{{ $ip->customer?->name ?? '-' }}</td><td class="px-4 py-3 font-mono">{{ $ip->subscription_code ?? '-' }}</td><td class="px-4 py-3">{{ $ip->assigned_at?->format('Y-m-d') ?? '-' }}</td></tr>@empty<tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">No IP addresses match these filters.</td></tr>@endforelse</tbody></table></div>
    {{ $ipAddresses->links() }}
</div>
@endsection
