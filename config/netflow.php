<?php

return [
    'collector_host' => env('NETFLOW_COLLECTOR_HOST'),
    'collector_bind_host' => env('NETFLOW_COLLECTOR_BIND_HOST', '0.0.0.0'),
    'collector_port' => (int) env('NETFLOW_COLLECTOR_PORT', 2055),
    'python' => env('NETFLOW_PYTHON_BINARY', 'python3'),
    'test_window_seconds' => (int) env('NETFLOW_TEST_WINDOW_SECONDS', 60),
];
