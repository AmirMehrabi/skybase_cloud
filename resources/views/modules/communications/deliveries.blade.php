@extends('layouts.admin')
@section('title', 'Delivery activity')
@section('content')
<x-communication-nav />
<x-ui.page-header title="Delivery activity" :description="$isCampaign ? 'Follow campaign delivery results and identify customers who need another attempt.' : 'Track automatic notifications and test messages across email and SMS.'" icon="activity" :eyebrow="$isCampaign ? 'Campaigns' : 'Notifications'" />
<div class="mb-5 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs leading-5 text-slate-500">Accepted means the gateway received the message. Delivered confirms receipt where supported. Unknown outcomes need verification before retrying.</div>
<form method="GET" class="mb-5 flex flex-wrap items-end gap-4"><x-ui.input.select name="status" label="Status" :options="array_combine(['pending','sending','retrying','accepted','delivered','failed','unknown','skipped','cancelled','simulated'], ['Pending','Sending','Retrying','Accepted','Delivered','Failed','Unknown','Skipped','Cancelled','Simulated'])" :value="request('status')" placeholder="All statuses" /><x-ui.input.select name="channel" label="Channel" :options="['email'=>'Email','sms'=>'SMS']" :value="request('channel')" placeholder="All channels" /><x-ui.button type="submit" variant="secondary">Filter</x-ui.button></form>
<x-ui.card>@include('modules.communications.delivery-table')</x-ui.card>
@endsection
