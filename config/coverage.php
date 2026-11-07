<?php

return [
    'tiles' => env('COVERAGE_TILE_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
    'attribution' => env('COVERAGE_TILE_ATTRIBUTION', '&copy; OpenStreetMap contributors'),
];
