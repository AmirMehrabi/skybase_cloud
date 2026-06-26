@extends('layouts.admin')

@section('title', 'Role Details')

@section('content')
<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
        <a href="{{ route('admin.tenant.roles.index') }}" class="mb-2 flex items-center gap-2 text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Roles</span>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $role->name }}</h1>
        <p class="mt-1 text-gray-600">{{ $role->description ?: 'No description provided.' }}</p>
    </div>
    @if(auth()->user()?->hasPermission('roles.write'))
        <a href="{{ route('admin.tenant.roles.edit', $role) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            <i class="fas fa-edit"></i>
            <span>Edit Role</span>
        </a>
    @endif
</div>

<div class="mb-6 grid gap-4 md:grid-cols-3">
    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-gray-500">Assigned users</div>
        <div class="mt-2 text-2xl font-bold text-gray-900">{{ $role->users_count }}</div>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-gray-500">Permissions</div>
        <div class="mt-2 text-2xl font-bold text-gray-900">{{ in_array('*', $role->permissions ?? [], true) ? 'All' : count($role->permissions ?? []) }}</div>
    </div>
</div>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900">Permission Matrix</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">بخش</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">مشاهده</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">ایجاد و ویرایش</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">حذف</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">عملیات ویژه</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($modules as $moduleKey => $module)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900">{{ $module['label'] }}</div>
                            <div class="mt-1 text-xs text-gray-500">{{ $module['description'] }}</div>
                        </td>
                        @foreach(['read', 'write', 'delete', 'actions'] as $action)
                            @php($permission = "{$moduleKey}.{$action}")
                            <td class="px-4 py-4 text-center">
                                @if(array_key_exists($action, $module['actions']))
                                    @if($role->hasPermission($permission))
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-700">✓</span>
                                    @else
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-gray-400">-</span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-300">-</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
