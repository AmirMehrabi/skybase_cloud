<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SbukErpClient
{
    /** @return array<int, array<string, mixed>> */
    public function personnel(): array
    {
        if (! config('services.sbuk_erp.enabled')) {
            throw new RuntimeException('SBUK ERP integration is disabled.');
        }
        if (blank(config('services.sbuk_erp.token'))) {
            throw new RuntimeException('SBUK ERP credentials are not configured.');
        }

        $authorization = trim((string) config('services.sbuk_erp.token'));

        try {
            $response = Http::connectTimeout((int) config('services.sbuk_erp.connect_timeout', 10))
                ->timeout((int) config('services.sbuk_erp.timeout', 120))
                ->withOptions(['allow_redirects' => false])
                ->withHeaders([
                    'Authorization' => $authorization,
                    'Accept' => 'application/json',
                ])
                ->post(rtrim((string) config('services.sbuk_erp.base_url'), '/').'/ServiceBuilder', [
                    'serviceId' => (int) config('services.sbuk_erp.service_id', 2112),
                    'isAllRecord' => 1,
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('SBUK ERP could not be reached. Check the connection and try again.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('SBUK ERP rejected the request (HTTP '.$response->status().').');
        }

        try {
            $payload = json_decode($response->body(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new RuntimeException('SBUK ERP returned invalid JSON.');
        }

        if (is_string($payload)) {
            try {
                $payload = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new RuntimeException('SBUK ERP returned an invalid JSON-encoded response.');
            }
        }

        if (isset($payload['error']) || isset($payload['Error']) || isset($payload['success']) && $payload['success'] === false) {
            throw new RuntimeException('SBUK ERP reported an application error.');
        }
        if (! is_array($payload) || ! array_is_list($payload)) {
            throw new RuntimeException('SBUK ERP returned an unexpected response structure.');
        }
        foreach ($payload as $record) {
            if (! is_array($record)) {
                throw new RuntimeException('SBUK ERP returned an unexpected personnel record.');
            }
        }

        return $payload;
    }
}
