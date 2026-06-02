<?php

return [
    'rrdtool' => env('RRDTOOL_BINARY', 'rrdtool'),
    'rrd_root' => storage_path('app/monitoring/rrd'),
    'step_seconds' => (int) env('MONITORING_RRD_STEP', 60),
    'ping_count' => (int) env('MONITORING_PING_COUNT', 5),
    'ping_timeout_seconds' => (int) env('MONITORING_PING_TIMEOUT', 2),
    'cache_seconds' => (int) env('MONITORING_CACHE_SECONDS', 5),
    'router_latency_warning_ms' => (float) env('MONITORING_ROUTER_LATENCY_WARNING_MS', 120),
    'router_packet_loss_warning_percent' => (float) env('MONITORING_ROUTER_PACKET_LOSS_WARNING', 5),
    'subscription_live_ttl_seconds' => (int) env('MONITORING_SUBSCRIPTION_LIVE_TTL', 5),
];
