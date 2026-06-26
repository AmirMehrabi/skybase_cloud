@extends('layouts.admin')

@section('title', 'Role Management')

@section('content')
<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Role Management</h1>
        <p class="mt-1 text-gray-600">Manage tenant roles and their access permissions.</p>
    </div>
    @if(auth()->user()?->hasPermission('roles.write'))
        <a href="{{ route('admin.tenant.roles.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            <i class="fas fa-plus"></i>
            <span>New Role</span>
        </a>
    @endif
</div>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Permissions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Users</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($roles as $role)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900">{{ $role->name }}</div>
                            <div class="mt-1 text-sm text-gray-500">{{ $role->description }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            @if(in_array('*', $role->permissions ?? [], true))
                                Full access
                            @else
                                {{ count($role->permissions ?? []) }} permissions
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $role->users_count }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2" x-data="{ deleting: false }">
                                <x-ui.action-icon href="{{ route('admin.tenant.roles.show', $role) }}" icon="view" label="View" />
                                @if(auth()->user()?->hasPermission('roles.write'))
                                    <x-ui.action-icon href="{{ route('admin.tenant.roles.edit', $role) }}" icon="edit" label="Edit" />
                                @endif
                                @if(auth()->user()?->hasPermission('roles.delete') && $role->users_count === 0 && $role->normalizedName() !== 'owner')
                                    <x-ui.action-icon as="button" icon="delete" label="Delete" @click="deleting = true" />
                                    <form x-ref="deleteForm" method="POST" action="{{ route('admin.tenant.roles.destroy', $role) }}" class="hidden">
                                        @csrf
                                        @method('delete')
                                    </form>
                                    <x-ui.delete-modal
                                        show="deleting"
                                        title="Delete Role"
                                        confirm-action="$refs.deleteForm.submit()"
                                        cancel-action="deleting = false"
                                        loading="false"
                                    />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
