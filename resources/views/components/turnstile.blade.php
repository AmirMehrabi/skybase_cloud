@props(['errorBag'])

<div class="mt-5">
    @if (config('services.turnstile.site_key'))
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-size="flexible" data-theme="light"></div>
        <noscript><p role="alert">Please enable JavaScript to complete CAPTCHA verification.</p></noscript>
        @once
            @push('scripts')
                <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endpush
        @endonce
    @else
        <p role="alert">Verification is temporarily unavailable. Please try again later.</p>
    @endif
    @if ($errors->getBag($errorBag)->has('cf-turnstile-response'))
        <p role="alert" class="mt-2 text-sm text-red-600">{{ $errors->getBag($errorBag)->first('cf-turnstile-response') }}</p>
    @endif
</div>
