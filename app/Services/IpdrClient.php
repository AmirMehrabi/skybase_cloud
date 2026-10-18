<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IpdrClient
{
    private function request(string $tenantId): PendingRequest
    {
        if (! config('ipdr.enabled') || ! config('ipdr.url') || strlen((string) config('ipdr.integration_token')) < 32) {
            throw new RuntimeException('The IPDR integration is not configured.');
        }

        return Http::baseUrl(rtrim(config('ipdr.url'), '/'))->acceptJson()->withToken(config('ipdr.integration_token'))
            ->withHeaders(['X-Skybase-Tenant' => $tenantId])->connectTimeout(5)->timeout(30)->retry(2, 250);
    }

    public function inventory(string $tenantId, array $routers, array $bindings): void
    {
        $response = $this->request($tenantId)->post('/api/internal/v1/skybase/inventory', compact('routers', 'bindings'))->throw()->json();
        if (($response['accepted'] ?? false) !== true) {
            throw new RuntimeException('IPDR did not acknowledge inventory receipt.');
        }
    }

    public function report(string $tenantId, array $filters): array
    {
        return $this->request($tenantId)->get('/api/internal/v1/skybase/report', $filters)->throw()->json();
    }
}
