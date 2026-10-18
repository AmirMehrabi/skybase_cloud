<?php

return [
    'enabled' => (bool) env('IPDR_ENABLED', false),
    'url' => env('IPDR_URL'),
    'integration_token' => env('IPDR_INTEGRATION_TOKEN'),
    'collector_host' => env('IPDR_COLLECTOR_HOST'),
    'collector_port' => (int) env('IPDR_COLLECTOR_PORT', 2055),
    'radius_timezone' => env('IPDR_RADIUS_TIMEZONE', 'UTC'),
    'radius_stale_minutes' => (int) env('IPDR_RADIUS_STALE_MINUTES', 15),
    'radius_history_days' => (int) env('IPDR_RADIUS_HISTORY_DAYS', 30),
];
