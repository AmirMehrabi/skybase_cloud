@props(['guard'])

@php
    $slug = request()->string('sso_tenant')->trim()->limit(100, '')->toString();
    $sso = \App\Services\GovernmentSso::class;
    $tenant = $sso::tenant($slug);
    $settings = $tenant ? $sso::settings((string) $tenant->id) : [];
    $enabled = $sso::enabled($tenant, $settings, $guard);
    $loginRoute = $guard === 'customer' ? 'customer.login' : 'auth.login';
    $otherGuard = $guard === 'customer' ? 'web' : 'customer';
@endphp

@if ($enabled || $slug !== '' || config('app.cloud.enabled') || $errors->has('government_sso'))
<div class="mt-6 border-t border-slate-200 pt-4">
    @error('government_sso')
        <p role="alert" class="mb-3 text-sm text-red-700">{{ $message }}</p>
    @enderror
    @if ($enabled)
        <form method="POST" action="{{ route('government-sso.start', $guard) }}">
            @csrf
            <input type="hidden" name="sso_tenant" value="{{ $tenant->slug }}">
            <button type="submit" class="w-full rounded-lg border border-teal-700 px-4 py-3 font-semibold text-teal-800 hover:bg-teal-50">
                {{ $settings['label'] }}
            </button>
        </form>
        @if ($sso::enabled($tenant, $settings, $otherGuard))
            <a class="mt-3 block text-sm font-medium text-teal-800 underline" href="{{ route($otherGuard === 'customer' ? 'customer.login' : 'auth.login', ['sso_tenant' => $tenant->slug]) }}">
                {{ $otherGuard === 'customer' ? 'Customer portal sign-in' : 'Staff sign-in' }}
            </a>
        @endif
    @elseif ($slug !== '')
        <p class="text-sm text-slate-600">Government sign-in is unavailable for this tenant and account type.</p>
    @endif

    @if (config('app.cloud.enabled') && ! $enabled)
        <details class="mt-2">
            <summary class="cursor-pointer text-sm font-medium text-teal-800">Find government sign-in for your tenant</summary>
            <form method="GET" action="{{ route($loginRoute) }}" class="mt-3">
                <x-input.text name="sso_tenant" label="Tenant code" :value="$slug" :required="true" />
                <button type="submit" class="rounded-lg border border-teal-700 px-4 py-2 text-sm font-semibold text-teal-800 hover:bg-teal-50">Continue</button>
            </form>
        </details>
    @endif
</div>
@endif
