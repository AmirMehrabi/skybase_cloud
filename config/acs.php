<?php

return [
    'allowed_endpoints' => array_filter(array_map('trim', explode(',', env('ACS_ALLOWED_ENDPOINTS', 'http://127.0.0.1:7557')))),
    'timeout' => 15,
    'test_endpoint' => env('ACS_TEST_ENDPOINT'),
    'test_cwmp_url' => env('ACS_TEST_CWMP_URL'),
    'test_username' => env('ACS_TEST_USERNAME'),
    'test_password' => env('ACS_TEST_PASSWORD'),
];
