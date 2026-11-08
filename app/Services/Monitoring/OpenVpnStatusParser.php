<?php

namespace App\Services\Monitoring;

use Carbon\CarbonImmutable;
use RuntimeException;

class OpenVpnStatusParser
{
    public function parse(string $contents): array
    {
        $header = null;
        $timestamp = null;
        $clients = [];
        $ended = false;

        foreach (preg_split('/\r?\n/', trim($contents)) as $line) {
            $row = str_getcsv($line, ',', '"', '');
            if ($row[0] === 'TIME') {
                $timestamp = $row[2] ?? null;
            } elseif ($row[0] === 'HEADER' && ($row[1] ?? '') === 'CLIENT_LIST') {
                $header = array_slice($row, 2);
                if (array_diff(['Username', 'Virtual Address', 'Bytes Received', 'Bytes Sent', 'Connected Since (time_t)', 'Real Address'], $header) !== []) {
                    throw new RuntimeException('OpenVPN status header is missing required fields.');
                }
            } elseif ($row[0] === 'CLIENT_LIST') {
                if ($header === null || count($header) !== count($row) - 1) {
                    throw new RuntimeException('Incomplete OpenVPN client row.');
                }
                $client = array_combine($header, array_slice($row, 1));
                $username = $client['Username'] ?? '';
                if ($username === '' || $username === 'UNDEF' || isset($clients[$username])
                    || ! filter_var($client['Virtual Address'] ?? '', FILTER_VALIDATE_IP)
                    || ! ctype_digit($client['Bytes Received'] ?? '')
                    || ! ctype_digit($client['Bytes Sent'] ?? '')
                    || ! ctype_digit($client['Connected Since (time_t)'] ?? '')) {
                    throw new RuntimeException('Invalid or ambiguous OpenVPN client identity or counters.');
                }
                $clients[$username] = [
                    'vpn_ip' => $client['Virtual Address'],
                    'real_ip' => $client['Real Address'] ?? null,
                    'bytes_received' => (int) $client['Bytes Received'],
                    'bytes_sent' => (int) $client['Bytes Sent'],
                    'connected_at' => CarbonImmutable::createFromTimestampUTC((int) $client['Connected Since (time_t)']),
                ];
            } elseif ($row[0] === 'END') {
                $ended = true;
            }
        }

        if (! $ended || $header === null || ! ctype_digit($timestamp ?? '')) {
            throw new RuntimeException('Incomplete OpenVPN status snapshot.');
        }
        $observedAt = CarbonImmutable::createFromTimestampUTC((int) $timestamp);
        if ($observedAt->lt(now()->subSeconds(config('vpn.freshness_seconds'))) || $observedAt->gt(now()->addSeconds(30))) {
            throw new RuntimeException('OpenVPN status snapshot is stale or has an invalid timestamp.');
        }
        foreach ($clients as $client) {
            if ($client['connected_at']->gt($observedAt)) {
                throw new RuntimeException('OpenVPN connection time is later than its snapshot.');
            }
        }

        return ['observed_at' => $observedAt, 'clients' => $clients];
    }
}
