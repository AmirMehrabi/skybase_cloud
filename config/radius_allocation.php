<?php

return [
    'tokens' => json_decode(env('RADIUS_ALLOCATION_TOKENS', '{}'), true) ?: [],
    'pending_seconds' => 120,
];
