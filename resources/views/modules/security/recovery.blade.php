@extends('layouts.auth')
@section('title', 'Save your recovery codes')
@section('content')
<x-ui.card title="Save your recovery codes">
    <p>Store these codes securely. Each code can be used once if you lose access to your authenticator.</p>
    <ul>@foreach($codes as $code)<li><code>{{ $code }}</code></li>@endforeach</ul>
    <a href="{{ auth('web')->user()->isCentralAdmin() ? route('admin.super-admin.tenants.index') : route('profile.index') }}">Continue</a>
</x-ui.card>
@endsection
