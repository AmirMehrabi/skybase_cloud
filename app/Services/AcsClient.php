<?php

namespace App\Services;

use App\Models\AcsConnection;

interface AcsClient
{
    public function records(AcsConnection $connection, string $collection, array $query = [], array $options = []): array;

    public function submit(AcsConnection $connection, string $deviceId, array $task): array;
}
