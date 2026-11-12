@extends('layouts.customer')
@section('title', 'Pay invoice')
@section('page_title', 'Pay invoice')
@section('content')
<x-ui.card title="Continue to PayFast" subtitle="Complete your payment securely on PayFast.">
    <div class="space-y-5">
        <p class="text-sm text-slate-600">Amount: R{{ number_format($attempt->amount_minor / 100, 2) }} @if($attempt->environment === 'sandbox') · Sandbox test — your invoice will remain outstanding. @endif</p>
        <form method="POST" action="{{ $endpoint }}">
            @foreach($data as $name => $value)
                <x-ui.input.text type="hidden" :name="$name" :value="$value" />
            @endforeach
            <x-ui.button type="submit" variant="brand">Continue to PayFast</x-ui.button>
        </form>
    </div>
</x-ui.card>
@endsection
