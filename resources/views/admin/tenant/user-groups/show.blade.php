@extends('layouts.admin')

@section('title', 'User Group Details')

@section('content')
<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div><a href="{{ route('admin.tenant.user-groups.index') }}" class="mb-2 flex items-center gap-2 text-blue-600 hover:text-blue-800"><i class="fas fa-arrow-left"></i><span>Back to User Groups</span></a><h1 class="text-2xl font-bold text-gray-900">{{ $userGroup->name }}</h1><p class="mt-1 text-gray-600">{{ $userGroup->description ?: 'No description provided.' }}</p></div>
    @if(auth()->user()?->hasPermission('user_groups.write'))<a href="{{ route('admin.tenant.user-groups.edit', $userGroup) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit User Group</a>@endif
</div>

<div class="grid gap-4 md:grid-cols-5">
    @foreach(['Users' => $userGroup->users_count, 'Organizations' => $userGroup->organizations_count, 'Customers' => $userGroup->customers_count, 'Subscriptions' => $userGroup->subscriptions_count, 'Sites' => $userGroup->sites_count] as $label => $count)
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"><div class="text-sm text-gray-500">{{ $label }}</div><div class="mt-2 text-2xl font-bold text-gray-900">{{ $count }}</div></div>
    @endforeach
</div>

@if(auth()->user()?->hasPermission('user_groups.delete'))
<div class="mt-8 rounded-lg border border-red-200 bg-red-50 p-5" x-data="{ deleting: false }">
    <h2 class="font-semibold text-red-900">Delete User Group</h2><p class="mt-1 text-sm text-red-700">Deletion is available only after every associated record has been reassigned.</p>
    <button type="button" @click="deleting = true" class="mt-4 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Delete User Group</button>
    <form x-ref="deleteForm" method="POST" action="{{ route('admin.tenant.user-groups.destroy', $userGroup) }}" class="hidden">@csrf @method('delete')</form>
    <x-ui.delete-modal show="deleting" title="Delete User Group" confirm-action="$refs.deleteForm.submit()" cancel-action="deleting = false" loading="false" />
</div>
@endif
@endsection
