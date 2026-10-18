@extends('layouts.admin')
@section('title', 'IPDR Flow Records')
@section('content')
<h1>IPDR flow records — {{ $subscription->subscription_code }}</h1>
<p><a href="{{ route('subscriptions.show', $subscription) }}">Back to subscription</a></p>
<p>@foreach(['1 hour' => 1, '24 hours' => 24, '7 days' => 168] as $label => $hours) <a href="{{ route('subscriptions.ipdr', ['subscription' => $subscription, 'from' => now('UTC')->subHours($hours)->toIso8601String(), 'to' => now('UTC')->toIso8601String()]) }}">{{ $label }}</a> @endforeach</p>
<p>Internet traffic attributed through historical address and session ownership. RADIUS remains the quota source.</p>
@if($error)
<p role="alert">{{ $error }}</p>
@else
<p>{{ $report['from'] }} — {{ $report['to'] }} (UTC)</p>
<p>Download: {{ number_format(($report['usage']['download_bytes'] ?? 0) / 1048576, 2) }} MiB · Upload: {{ number_format(($report['usage']['upload_bytes'] ?? 0) / 1048576, 2) }} MiB · Internal: {{ number_format(($report['usage']['internal_bytes'] ?? 0) / 1048576, 2) }} MiB</p>
@if($report['usage']['estimated'] ?? false)<p>Some counters are estimated across ownership boundaries.</p>@endif
<p>Observation rows show the original exported counters; usage totals above include only the portions attributed to this subscription.</p>
<table><thead><tr><th>Start (UTC)</th><th>Source</th><th>Destination</th><th>Protocol</th><th>Bytes</th><th>Mapping</th></tr></thead><tbody>
@forelse($report['latest_flows'] ?? [] as $flow)
<tr><td>{{ $flow['flow_start'] }}</td><td>{{ $flow['source'] }}</td><td>{{ $flow['destination'] }}</td><td>{{ $flow['protocol'] }}</td><td>{{ number_format($flow['bytes']) }}</td><td>{{ $flow['mapping_status'] }}</td></tr>
@empty<tr><td colspan="6">No attributed flows in this window.</td></tr>@endforelse
</tbody></table>
@endif
@endsection
