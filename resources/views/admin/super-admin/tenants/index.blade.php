@extends('layouts.admin')

@section('title', 'Tenants Management')

@push('styles')
<style>
    .tenant-card {
        @apply bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition;
    }
</style>
@endpush

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Tenants Management</h1>
    <p class="text-gray-600 mt-1">Manage all ISP tenant accounts</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Tenants</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $tenants->total() }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-building text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Active</p>
                <p class="text-3xl font-bold text-green-600 mt-2">{{ \App\Models\Tenant::active()->count() }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Pending</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2">{{ \App\Models\Tenant::pending()->count() }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Suspended</p>
                <p class="text-3xl font-bold text-red-600 mt-2">{{ \App\Models\Tenant::suspended()->count() }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-ban text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Search -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input
                type="text"
                placeholder="Search by company name or email..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                id="search-tenants"
            >
        </div>
        <div class="flex gap-2">
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="suspended">Suspended</option>
            </select>
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Plans</option>
                <option value="1">Basic</option>
                <option value="2">Pro</option>
                <option value="3">Enterprise</option>
            </select>
        </div>
    </div>
</div>

<!-- Tenants Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trial</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($tenants as $tenant)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                            {{ strtoupper(substr($tenant->company_name, 0, 2)) }}
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">{{ $tenant->company_name }}</div>
                            <div class="text-sm text-gray-500">{{ $tenant->email }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    @if($tenant->plan)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $tenant->plan->name }}
                        </span>
                    @else
                        <span class="text-sm text-gray-400">No plan</span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    @if($tenant->status === 'active')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle"></i> Active
                        </span>
                    @elseif($tenant->status === 'pending')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock"></i> Pending
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-ban"></i> Suspended
                        </span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900">
                        <span class="font-medium">{{ $tenant->users_count }}</span> users
                        <span class="text-gray-400 mx-1">•</span>
                        <span class="font-medium">{{ $tenant->customers_count }}</span> customers
                        <span class="text-gray-400 mx-1">•</span>
                        <span class="font-medium">{{ $tenant->routers_count }}</span> routers
                    </div>
                </td>
                <td class="px-6 py-4">
                    @if($tenant->isOnTrial())
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            <i class="fas fa-hourglass-half"></i> {{ $tenant->trial_ends_at->diffForHumans() }}
                        </span>
                    @elseif($tenant->hasExpiredTrial())
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-exclamation-triangle"></i> Expired
                        </span>
                    @else
                        <span class="text-sm text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    {{ $tenant->created_at->format('M d, Y') }}
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.super-admin.tenants.show', $tenant) }}" class="text-blue-600 hover:text-blue-900" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.super-admin.tenants.edit', $tenant) }}" class="text-gray-600 hover:text-gray-900" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($tenant->isActive())
                        <form method="POST" action="{{ route('admin.super-admin.tenants.suspend', $tenant) }}" class="inline" onsubmit="return confirm('Are you sure you want to suspend this tenant?')">
                            @csrf
                            <button type="submit" class="text-yellow-600 hover:text-yellow-900" title="Suspend">
                                <i class="fas fa-pause"></i>
                            </button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('admin.super-admin.tenants.activate', $tenant) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-900" title="Activate">
                                <i class="fas fa-play"></i>
                            </button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('admin.super-admin.tenants.destroy', $tenant) }}" class="inline" onsubmit="return confirm('Are you sure? This will permanently delete this tenant and all associated data.')">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center">
                    <div class="text-gray-400">
                        <i class="fas fa-building text-4xl mb-4"></i>
                        <p class="text-lg font-medium">No tenants found</p>
                        <p class="text-sm">Get started by registering a new ISP account.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pagination -->
    @if($tenants->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-500">
            Showing {{ $tenants->firstItem() }} to {{ $tenants->lastItem() }} of {{ $tenants->total() }} tenants
        </div>
        {{ $tenants->links() }}
    </div>
    @endif
</div>
@endsection
