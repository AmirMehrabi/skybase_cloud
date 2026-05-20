@extends('layouts.admin')

@section('title', 'VPN User Details')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $vpnUser->username }}</h1>
            <p class="mt-1 text-sm text-gray-500">OpenVPN account details</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('vpn-users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Back</a>
            <a href="{{ route('vpn-users.edit', $vpnUser) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Edit</a>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500">Username</dt>
                <dd class="mt-1 font-mono text-sm text-gray-900">{{ $vpnUser->username }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1">
                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $vpnUser->active ? 'border-green-200 bg-green-100 text-green-800' : 'border-gray-200 bg-gray-100 text-gray-700' }}">
                        {{ $vpnUser->active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $vpnUser->last_login_at?->format('M d, Y H:i') ?? 'Never' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Created</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $vpnUser->created_at?->format('M d, Y H:i') }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
