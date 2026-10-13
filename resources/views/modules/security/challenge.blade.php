@extends('layouts.auth')
@section('title', 'Verify your identity')
@section('content')
<x-ui.card title="Verify your identity">
    <form method="POST" action="{{ route('mfa.verify') }}">
        @csrf
        <x-input.text name="code" label="Authenticator code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" />
        <x-input.text name="recovery_code" label="Or use a recovery code" autocomplete="off" />
        <x-ui.button type="submit">Verify</x-ui.button>
    </form>
</x-ui.card>
@endsection
