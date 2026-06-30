@extends('layouts.admin')

@section('title', 'Profile')

@section('content')
<div class="max-w-2xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">My Profile</h1>
        <p class="text-sm text-slate-500 mt-1">Manage your account settings and preferences.</p>
    </div>

    <!-- Profile Card -->
    <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 rounded-full bg-[#0d2f35] flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($user->status === 'active') bg-emerald-50 text-emerald-700
                        @else bg-slate-100 text-slate-700
                        @endif">
                        @if($user->status === 'active')
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1"></span>
                        @endif
                        {{ ucfirst($user->status) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($user->role === 'owner') bg-violet-50 text-violet-700
                        @elseif($user->role === 'admin') bg-sky-50 text-sky-700
                        @elseif($user->role === 'billing') bg-emerald-50 text-emerald-700
                        @elseif($user->role === 'support') bg-amber-50 text-amber-700
                        @elseif($user->role === 'noc') bg-orange-50 text-orange-700
                        @else bg-slate-100 text-slate-700
                        @endif">
                        {{ $user->getRoleDisplayName() }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm p-4 bg-slate-50 rounded-xl">
            <div>
                <span class="text-slate-500">Member since</span>
                <p class="font-medium text-slate-900">{{ $user->created_at->format('M d, Y') }}</p>
            </div>
            <div>
                <span class="text-slate-500">Last login</span>
                <p class="font-medium text-slate-900">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</p>
            </div>
        </div>
    </div>

    <!-- Edit Profile -->
    <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Edit Profile</h3>

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <x-input.text
                id="name"
                name="name"
                label="Full Name"
                :value="$user->name"
                :required="true"
            />

            <x-input.text
                id="email"
                name="email"
                label="Email Address"
                :value="$user->email"
                :required="true"
            />

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="submit" class="rounded-xl bg-[#0d2f35] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0a2529] transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Change Password</h3>

        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PUT')

            <x-input.password
                id="current_password"
                name="current_password"
                label="Current Password"
                :required="true"
                :showToggle="true"
            />

            <x-input.password
                id="password"
                name="password"
                label="New Password"
                :required="true"
                :minlength="8"
                :showToggle="true"
            />

            <x-input.password
                id="password_confirmation"
                name="password_confirmation"
                label="Confirm New Password"
                :required="true"
                :minlength="8"
                :showToggle="true"
            />

            <p class="text-xs text-slate-500 mb-4">Must be at least 8 characters.</p>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="submit" class="rounded-xl bg-[#0d2f35] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0a2529] transition">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
