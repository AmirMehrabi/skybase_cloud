<?php

return [
    'status_file' => env('OPENVPN_STATUS_FILE', '/var/lib/skybase-vpn/status.csv'),
    'reservations_file' => env('OPENVPN_RESERVATIONS_FILE', '/var/lib/skybase-vpn/reservations.json'),
    'freshness_seconds' => 180,
];
