@extends('layouts.customer')

@section('title', 'Profile')
@section('page_title', 'Profile')

@section('content')
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <section class="rounded-2xl border border-slate-900/10 bg-white p-6 shadow-sm">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#0d2f35] text-xl font-bold text-white">
            {{ strtoupper(substr($customer->full_name, 0, 1)) }}
        </div>
        <h2 class="mt-4 text-xl font-bold text-slate-950">{{ $customer->full_name }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $customer->customer_code }}</p>

        <dl class="mt-6 space-y-4 text-sm">
            <div>
                <dt class="text-slate-500">Email</dt>
                <dd class="mt-1 break-words font-medium text-slate-900">{{ $customer->email }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Phone</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ $customer->mobile ?: ($customer->phone ?: 'Not provided') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Address</dt>
                <dd class="mt-1 font-medium text-slate-900">
                    {{ collect([$customer->address_line1, $customer->address_line2, $customer->city, $customer->state, $customer->country])->filter()->join(', ') ?: 'Not provided' }}
                </dd>
            </div>
            <div>
                <dt class="text-slate-500">Account status</dt>
                <dd class="mt-1 font-medium capitalize text-slate-900">{{ $customer->status }}</dd>
            </div>
        </dl>
    </section>

    <section class="rounded-2xl border border-slate-900/10 bg-white p-6 shadow-sm xl:col-span-2">
        <div>
            <h2 class="text-xl font-bold text-slate-950">Change password</h2>
            <p class="mt-1 text-sm text-slate-500">Use a unique password with at least eight characters.</p>
        </div>

        <form method="POST" action="{{ route('customer.profile.password.update') }}" class="mt-6 max-w-xl">
            @csrf
            @method('PATCH')

            <x-input.password id="current_password" name="current_password" label="Current password" required minlength="8" />
            @error('current_password')
                <p class="-mt-2 mb-4 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <x-input.password id="password" name="password" label="New password" required minlength="8" />
            @error('password')
                <p class="-mt-2 mb-4 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <x-input.password id="password_confirmation" name="password_confirmation" label="Confirm new password" required minlength="8" />

            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#0d2f35] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#123f3d] focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2">
                Update password
            </button>
        </form>
    </section>
</div>
@endsection
