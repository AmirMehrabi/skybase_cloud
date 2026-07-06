@extends('layouts.admin')
@section('title', 'Edit '.$workOrder->work_order_number)
@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div><h1 class="text-2xl font-bold text-slate-950">Edit {{ $workOrder->work_order_number }}</h1><p class="mt-1 text-sm text-slate-600">Draft scope and service-location details.</p></div>
    @include('modules.work-orders.form')
</div>
@endsection
