<?php

namespace App\Services\Ldap;

use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\Entry;

class LdapConnectionFactory
{
    /**
     * @param  array<string, mixed>  $connectionSettings
     */
    public function register(string $tenantId, array $connectionSettings): string
    {
        $name = 'tenant_'.str_replace('-', '_', $tenantId);

        Container::addConnection(new Connection([
            'hosts' => $this->hosts($connectionSettings),
            'username' => $connectionSettings['username'] ?? null,
            'password' => $connectionSettings['password'] ?? null,
            'port' => (int) ($connectionSettings['port'] ?? 389),
            'base_dn' => $connectionSettings['base_dn'] ?? null,
            'timeout' => (int) ($connectionSettings['timeout'] ?? 5),
            'use_tls' => (bool) ($connectionSettings['use_tls'] ?? false),
            'use_starttls' => (bool) ($connectionSettings['use_starttls'] ?? false),
        ]), $name);

        return $name;
    }

    /**
     * @param  array<string, mixed>  $connectionSettings
     */
    public function test(string $tenantId, array $connectionSettings): void
    {
        Entry::on($this->register($tenantId, $connectionSettings))
            ->in($connectionSettings['base_dn'] ?? null)
            ->first(['dn']);
    }

    /**
     * @param  array<string, mixed>  $connectionSettings
     * @return array<int, string>
     */
    private function hosts(array $connectionSettings): array
    {
        $hosts = $connectionSettings['hosts'] ?? [];

        if (is_string($hosts)) {
            $hosts = preg_split('/[\s,]+/', $hosts, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return array_values(array_filter(array_map('trim', (array) $hosts)));
    }
}
