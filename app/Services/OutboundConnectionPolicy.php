<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\HttpFoundation\IpUtils;

class OutboundConnectionPolicy
{
    public function resolve(string $host, int $port, ?string $tenantId = null, bool $configuredDestination = false): string
    {
        $host = strtolower(trim($host));
        if ($host === '' || ($port < 1 || $port > 65535 || (! $configuredDestination && ! in_array($port, config('security.outbound_ports', []), true)))
            || (! filter_var($host, FILTER_VALIDATE_IP) && preg_match('/[\s\x00-\x1f\x7f\/\\\\:@]/', $host))) {
            throw new RuntimeException('Outbound destination is not approved.');
        }
        if ($configuredDestination && ! config('security.restrict_configured_destinations')) {
            return $host;
        }
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->addresses($host);
        if ($addresses === []) {
            throw new RuntimeException('Outbound destination could not be resolved.');
        }
        $networks = config('security.outbound_tenant_networks.'.($tenantId ?? tenant_id()), config('security.outbound_networks', []));
        $approvedHost = $configuredDestination || in_array($host, config('security.outbound_hosts', []), true);
        foreach ($addresses as $address) {
            if (IpUtils::checkIp($address, ['127.0.0.0/8', '0.0.0.0/8', '169.254.0.0/16', '224.0.0.0/4', '240.0.0.0/4', '::/128', '::1/128', 'fe80::/10', 'ff00::/8', '::ffff:0:0/96'])
                || (! $approvedHost && ! IpUtils::checkIp($address, $networks))) {
                throw new RuntimeException('Outbound destination is not approved.');
            }
        }

        return $addresses[0];
    }

    /** @return list<string> */
    protected function addresses(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        return array_values(array_filter(array_map(fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null, $records)));
    }
}
