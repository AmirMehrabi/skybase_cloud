@extends(config('skybase_billing.console_host_enabled') && request()->getHost() === config('skybase_billing.console_host') ? 'layouts.console' : 'layouts.auth')
@section('title', 'Set up multi-factor authentication')
@section('content')
<x-ui.card title="Protect your account">
    <p>Add this key to your authenticator app, then enter the six-digit code.</p>
    <p><code>{{ $secret }}</code></p>
    <a href="{{ $uri }}">Open authenticator app</a>
    <form method="POST" action="{{ route('mfa.confirm') }}">
        @csrf
        <x-input.password name="current_password" label="Current password" required autocomplete="current-password" />
        <x-input.text name="code" label="Authenticator code" required inputmode="numeric" autocomplete="one-time-code" maxlength="6" />
        <x-ui.button type="submit">Enable protection</x-ui.button>
    </form>
</x-ui.card>
@endsection
