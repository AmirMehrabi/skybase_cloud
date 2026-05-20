<?php

namespace App\Services\Ldap;

use Illuminate\Support\Facades\Log;
use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\Entry;
use Throwable;

class LdapConnectionFactory
{
    /**
     * @param  array<string, mixed>  $connectionSettings
     */
    public function register(string $tenantId, array $connectionSettings): string
    {
        $name = 'tenant_'.str_replace('-', '_', $tenantId);
        $config = [
            'hosts' => $this->hosts($connectionSettings),
            'username' => $connectionSettings['username'] ?? null,
            'password' => $connectionSettings['password'] ?? null,
            'port' => (int) ($connectionSettings['port'] ?? 389),
            'base_dn' => $connectionSettings['base_dn'] ?? null,
            'timeout' => (int) ($connectionSettings['timeout'] ?? 5),
            'use_tls' => (bool) ($connectionSettings['use_tls'] ?? false),
            'use_starttls' => (bool) ($connectionSettings['use_starttls'] ?? false),
        ];

        Container::addConnection(new Connection($config), $name);

        return $name;
    }

    /**
     * @param  array<string, mixed>  $connectionSettings
     */
    public function test(string $tenantId, array $connectionSettings): void
    {
        $context = $this->logContext($tenantId, $connectionSettings);

        Log::info('LDAP connection test started.', $context);

        try {
            $entry = Entry::on($this->register($tenantId, $connectionSettings))
                ->in($connectionSettings['base_dn'] ?? null)
                ->first(['dn']);
        } catch (Throwable $exception) {
            Log::error('LDAP connection test failed.', $context + [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        Log::info('LDAP connection test completed.', $context + [
            'first_dn' => $entry?->getDn(),
        ]);
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

    /**
     * @param  array<string, mixed>  $connectionSettings
     * @return array<string, mixed>
     */
    public function logContext(string $tenantId, array $connectionSettings): array
    {
        return [
            'tenant_id' => $tenantId,
            'hosts' => $this->hosts($connectionSettings),
            'port' => (int) ($connectionSettings['port'] ?? 389),
            'base_dn' => $connectionSettings['base_dn'] ?? null,
            'username' => $connectionSettings['username'] ?? null,
            'timeout' => (int) ($connectionSettings['timeout'] ?? 5),
            'use_tls' => (bool) ($connectionSettings['use_tls'] ?? false),
            'use_starttls' => (bool) ($connectionSettings['use_starttls'] ?? false),
            'password_present' => filled($connectionSettings['password'] ?? null),
        ];
    }
}
