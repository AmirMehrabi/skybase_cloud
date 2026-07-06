@extends('layouts.admin')
@section('title', 'New Work Order')
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div><h1 class="text-2xl font-bold text-slate-950">New Work Order</h1><p class="mt-1 text-sm text-slate-600">Create field work before or after a subscription exists.</p></div>
    @include('modules.work-orders.form')
</div>
@endsection
