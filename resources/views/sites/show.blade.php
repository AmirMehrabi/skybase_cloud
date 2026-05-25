@extends('layouts.admin')

@section('title', $site->name)

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $site->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $site->code }} · {{ $site->address ?: 'No address set' }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('sites.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">Back</a>
            <a href="{{ route('sites.edit', $site) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Edit Site</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Routers</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $site->routers_count }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Online</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $site->online_routers_count }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Offline</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $site->offline_routers_count }}</p>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Status</p>
            <p class="text-3xl font-bold text-gray-900 mt-2 capitalize">{{ $site->status }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm lg:col-span-1">
            <h2 class="text-lg font-semibold text-gray-900">Location</h2>
            <dl class="mt-4 space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Latitude</dt>
                    <dd class="text-sm text-gray-900 font-mono mt-1">{{ $site->latitude }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Longitude</dt>
                    <dd class="text-sm text-gray-900 font-mono mt-1">{{ $site->longitude }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="text-sm text-gray-900 mt-1">{{ $site->description ?: 'No description set.' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Assigned Routers</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Router</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($site->routers as $router)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $router->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $router->vendor }} {{ $router->model }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 font-mono">{{ $router->ip_address }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $router->status === 'online' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200' }}">{{ ucfirst($router->status ?? 'offline') }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('routers.show', $router) }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">No routers are assigned to this site.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
