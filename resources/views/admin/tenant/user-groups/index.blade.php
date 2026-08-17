@extends('layouts.admin')

@section('title', 'User Groups')

@section('content')
<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div><h1 class="text-2xl font-bold text-gray-900">User Groups</h1><p class="mt-1 text-gray-600">Control which tenant data each staff group can access.</p></div>
    @if(auth()->user()?->hasPermission('user_groups.write'))
        <a href="{{ route('admin.tenant.user-groups.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i class="fas fa-plus"></i><span>New User Group</span></a>
    @endif
</div>

<form method="GET" class="mb-6 flex gap-3">
    <x-input.text name="search" :value="request('search')" placeholder="Search User Groups" />
    <button class="mb-4 rounded-lg bg-slate-800 px-5 py-2 text-sm font-semibold text-white">Search</button>
</form>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto"><table class="w-full">
        <thead class="border-b border-gray-200 bg-gray-50"><tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Group</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Users</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Operational records</th>
            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-200">
        @forelse($groups as $group)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4"><div class="font-semibold text-gray-900">{{ $group->name }}</div><div class="mt-1 text-sm text-gray-500">{{ $group->description ?: 'No description' }}</div></td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $group->users_count }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $group->customers_count + $group->organizations_count + $group->subscriptions_count + $group->sites_count }}</td>
                <td class="px-6 py-4"><div class="flex justify-end gap-2"><x-ui.action-icon href="{{ route('admin.tenant.user-groups.show', $group) }}" icon="view" label="View" />@if(auth()->user()?->hasPermission('user_groups.write'))<x-ui.action-icon href="{{ route('admin.tenant.user-groups.edit', $group) }}" icon="edit" label="Edit" />@endif</div></td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">No User Groups found.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
<div class="mt-6">{{ $groups->links() }}</div>
@endsection
