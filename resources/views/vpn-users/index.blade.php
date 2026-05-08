@extends('layouts.admin')

@section('title', 'VPN Users')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">VPN Users</h1>
            <p class="mt-1 text-sm text-gray-500">Manage OpenVPN user accounts for this tenant</p>
        </div>
        <a href="{{ route('vpn-users.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New VPN User
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Total Users</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Active</p>
            <p class="mt-2 text-3xl font-bold text-green-600">{{ $stats['active'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Inactive</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['inactive'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Recent Logins</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ $stats['recentLogins'] }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('vpn-users.index') }}" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="md:col-span-2">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by username..." class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <select name="active" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Statuses</option>
                <option value="1" @selected(($filters['active'] ?? '') === '1')>Active</option>
                <option value="0" @selected(($filters['active'] ?? '') === '0')>Inactive</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Filter</button>
                <a href="{{ route('vpn-users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Username</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Last Login</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Created</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($vpnUsers as $vpnUser)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm font-medium text-gray-900">{{ $vpnUser->username }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $vpnUser->active ? 'border-green-200 bg-green-100 text-green-800' : 'border-gray-200 bg-gray-100 text-gray-700' }}">
                                    {{ $vpnUser->active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $vpnUser->last_login_at?->format('M d, Y H:i') ?? 'Never' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $vpnUser->created_at?->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('vpn-users.show', $vpnUser) }}" class="rounded-lg p-1.5 text-gray-400 hover:bg-blue-50 hover:text-blue-600" title="View">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href="{{ route('vpn-users.edit', $vpnUser) }}" class="rounded-lg p-1.5 text-gray-400 hover:bg-green-50 hover:text-green-600" title="Edit">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No VPN users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $vpnUsers->links() }}
</div>
@endsection
