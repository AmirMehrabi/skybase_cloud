@extends('layouts.admin')

@section('title', 'VPN Users')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="vpnUsersIndex" x-cloak>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">VPN Users</h1>
            <p class="mt-1 text-sm text-gray-500">Manage OpenVPN user accounts and connection state</p>
        </div>
        <a href="{{ route('vpn-users.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New VPN User
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm" x-data="{ copied: false, config: @js($onboarding['config']) }">
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">First OpenVPN Client Onboarding</h2>
                    <p class="mt-1 text-sm text-gray-500">Create an active VPN user, save this client profile, then sign in with that VPN username and password.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="copyConfig(config).then(() => { copied = true; setTimeout(() => copied = false, 1600) })" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span x-text="copied ? 'Copied' : 'Copy Config'"></span>
                    </button>
                    <button type="button" @click="downloadConfig(config)" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0118 4.414V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Download client.ovpn
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 p-6 xl:grid-cols-[minmax(0,360px)_1fr]">
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:grid-cols-1">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Remote Host</p>
                        <p class="mt-1 break-all font-mono text-sm text-gray-900">{{ $onboarding['remote_host'] }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Protocol</p>
                        <p class="mt-1 font-mono text-sm uppercase text-gray-900">{{ $onboarding['protocol'] }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Port</p>
                        <p class="mt-1 font-mono text-sm text-gray-900">{{ $onboarding['remote_port'] }}</p>
                    </div>
                </div>

                <ol class="space-y-3 text-sm text-gray-700">
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">1</span>
                        <span>Create a VPN user from this page and keep the account active.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">2</span>
                        <span>Download or copy the generated <span class="font-mono">client.ovpn</span> profile.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">3</span>
                        <span>Import it into OpenVPN and authenticate with the VPN username and password.</span>
                    </li>
                </ol>
            </div>

            <div>
                <label for="openvpn-client-config" class="mb-2 block text-sm font-medium text-gray-700">Generated client.ovpn</label>
                <textarea id="openvpn-client-config" readonly rows="18" class="block w-full resize-y rounded-xl border border-gray-300 bg-slate-950 p-4 font-mono text-xs leading-5 text-slate-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $onboarding['config'] }}</textarea>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Total Users</p>
            <p class="mt-2 text-3xl font-bold text-gray-900" x-text="stats.total"></p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Online</p>
            <p class="mt-2 text-3xl font-bold text-green-600" x-text="stats.online"></p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Offline</p>
            <p class="mt-2 text-3xl font-bold text-gray-900" x-text="stats.offline"></p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Active Accounts</p>
            <p class="mt-2 text-3xl font-bold text-blue-600" x-text="stats.active"></p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Filters</h3>
            <button x-show="hasActiveFilters" @click="clearFilters()" class="text-sm font-medium text-blue-600 hover:text-blue-700" style="display: none;">
                Clear All
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <input type="text" x-model="search" placeholder="Search by username..." class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <select x-model="active" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Accounts</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <select x-model="online" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Connections</option>
                <option value="1">Online</option>
                <option value="0">Offline</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Username</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Connection</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Last Seen</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Connected</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Disconnected</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Traffic</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-for="vpnUser in vpnUsers" :key="vpnUser.id">
                        <tr class="transition-colors hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-mono text-sm font-medium text-gray-900" x-text="vpnUser.username"></span>
                                    <span class="text-xs" :class="vpnUser.active ? 'text-green-600' : 'text-gray-500'" x-text="vpnUser.active ? 'Active account' : 'Inactive account'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium" :class="vpnUser.online ? 'border-green-200 bg-green-100 text-green-800' : 'border-gray-200 bg-gray-100 text-gray-700'">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="vpnUser.online ? 'bg-green-500' : 'bg-gray-400'"></span>
                                        <span x-text="vpnUser.online ? 'Online' : 'Offline'"></span>
                                    </span>
                                    <span class="font-mono text-xs text-gray-500" x-text="vpnUser.vpn_ip || vpnUser.real_ip || 'No IP recorded'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="vpnUser.last_seen_at || 'Never'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="vpnUser.connected_at || 'Not connected'"></td>
                            <td class="px-6 py-4 text-sm text-gray-600" x-text="vpnUser.disconnected_at || 'Not disconnected'"></td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col text-sm">
                                    <span class="text-gray-900">Rx <span class="font-semibold" x-text="formatBytes(vpnUser.bytes_received)"></span></span>
                                    <span class="text-gray-600">Tx <span class="font-semibold" x-text="formatBytes(vpnUser.bytes_sent)"></span></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <x-ui.action-icon x-bind:href="vpnUser.show_url" icon="view" label="View" />
                                    <x-ui.action-icon x-bind:href="vpnUser.edit_url" icon="edit" label="Edit" />
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!loading && vpnUsers.length === 0" style="display: none;">
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">No VPN users found.</td>
                    </tr>
                    <tr x-show="loading" style="display: none;">
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">Loading VPN users...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div x-show="totalVpnUsers > perPage" class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
            <div class="text-sm text-gray-500">
                Showing <span x-text="pagination.from ?? 0"></span> to <span x-text="pagination.to ?? 0"></span> of <span x-text="totalVpnUsers"></span> VPN users
            </div>
            <div class="flex items-center gap-2">
                <button @click="prevPage()" :disabled="currentPage === 1" class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">Previous</button>
                <template x-for="page in totalPages" :key="page">
                    <button @click="goToPage(page)" :class="currentPage === page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="min-w-[36px] rounded border px-3 py-1 text-sm" x-text="page"></button>
                </template>
                <button @click="nextPage()" :disabled="currentPage === totalPages" class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</div>
@endsection
