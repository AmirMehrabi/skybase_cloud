<?php

$defaultRrdRoot = storage_path('app/monitoring/rrd');
$basePath = base_path();

if (preg_match('#/releases/\d+$#', $basePath) === 1) {
    $defaultRrdRoot = dirname($basePath, 2).'/shared/storage/app/monitoring/rrd';
}

return [
    'rrdtool' => env('RRDTOOL_BINARY', 'rrdtool'),
    'rrd_root' => env('MONITORING_RRD_ROOT', $defaultRrdRoot),
    'step_seconds' => (int) env('MONITORING_RRD_STEP', 60),
    'ping_count' => (int) env('MONITORING_PING_COUNT', 5),
    'ping_timeout_seconds' => (int) env('MONITORING_PING_TIMEOUT', 2),
    'router_status_tcp_timeout_seconds' => (float) env('ROUTER_STATUS_TCP_TIMEOUT', 2),
    'router_status_ping_timeout_seconds' => (int) env('ROUTER_STATUS_PING_TIMEOUT', 2),
    'router_status_offline_failure_threshold' => (int) env('ROUTER_STATUS_OFFLINE_FAILURE_THRESHOLD', 3),
    'cache_seconds' => (int) env('MONITORING_CACHE_SECONDS', 5),
    'router_latency_warning_ms' => (float) env('MONITORING_ROUTER_LATENCY_WARNING_MS', 120),
    'router_packet_loss_warning_percent' => (float) env('MONITORING_ROUTER_PACKET_LOSS_WARNING', 5),
    'subscription_live_ttl_seconds' => (int) env('MONITORING_SUBSCRIPTION_LIVE_TTL', 5),
];
