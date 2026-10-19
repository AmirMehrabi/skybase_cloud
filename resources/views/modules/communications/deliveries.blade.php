@extends('layouts.admin')
@section('title', 'Delivery activity')
@section('content')
<x-communication-nav /><h1 class="mb-2 text-2xl font-semibold text-gray-900">Delivery activity</h1><p class="mb-6 text-sm text-gray-500">Track campaign, notification and test sends. Accepted is not confirmation of receipt. SMSPortal test mode records simulated sends.</p>
<form method="GET" class="mb-5 flex flex-wrap items-end gap-4"><x-ui.input.select name="status" label="Status" :options="array_combine(['pending','sending','retrying','accepted','delivered','failed','unknown','skipped','cancelled','simulated'], ['Pending','Sending','Retrying','Accepted','Delivered','Failed','Unknown','Skipped','Cancelled','Simulated'])" :value="request('status')" placeholder="All statuses" /><x-ui.input.select name="channel" label="Channel" :options="['email'=>'Email','sms'=>'SMS']" :value="request('channel')" placeholder="All channels" /><x-ui.button type="submit" variant="secondary">Filter</x-ui.button></form>
<x-ui.card>@include('modules.communications.delivery-table')</x-ui.card>
@endsection
