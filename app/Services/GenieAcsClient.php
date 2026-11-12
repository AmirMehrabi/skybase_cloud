<?php

namespace App\Services;

use App\Models\AcsConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GenieAcsClient implements AcsClient
{
    private function request(AcsConnection $connection): PendingRequest
    {
        if (! in_array($connection->base_url, config('acs.allowed_endpoints'), true)) {
            throw new RuntimeException('ACS endpoint is not approved.');
        }
        $request = Http::acceptJson()->connectTimeout(3)->timeout(config('acs.timeout'))->withOptions(['allow_redirects' => false]);
        if (filled($connection->username)) {
            $request->withBasicAuth($connection->username, $connection->password ?? '');
        }

        return $request;
    }

    public function records(AcsConnection $connection, string $collection, array $query = [], array $options = []): array
    {
        if (! in_array($collection, ['devices', 'tasks', 'faults'], true)) {
            throw new RuntimeException('Unsupported ACS collection.');
        }
        $response = $this->request($connection)->get($connection->base_url.'/'.$collection.'/', ['query' => json_encode((object) $query, JSON_THROW_ON_ERROR)] + $options)->throw();
        $data = $response->json();
        if (! is_array($data) || ! array_is_list($data)) {
            throw new RuntimeException('Invalid ACS response.');
        }

        return $data;
    }

    public function submit(AcsConnection $connection, string $deviceId, array $task): array
    {
        $response = $this->request($connection)->post($connection->base_url.'/devices/'.rawurlencode($deviceId).'/tasks', $task)->throw();
        if (! in_array($response->status(), [200, 202], true) || ! is_array($response->json())) {
            throw new RuntimeException('Invalid ACS task response.');
        }

        return ['completed' => $response->status() === 200, 'id' => $response->json('_id')];
    }
}
