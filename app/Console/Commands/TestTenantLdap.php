<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Ldap\LdapConnectionFactory;
use App\Services\Ldap\LdapSyncService;
use Illuminate\Console\Command;
use Throwable;

class TestTenantLdap extends Command
{
    protected $signature = 'ldap:tenant-test
        {tenant? : Tenant ID to test saved LDAP settings for}
        {--host= : LDAP host for an ad-hoc test}
        {--port=389 : LDAP port for an ad-hoc test}
        {--base-dn= : LDAP base DN for an ad-hoc test}
        {--username= : Bind DN / username for an ad-hoc test}
        {--password= : Bind password for an ad-hoc test}
        {--guess-user= : Try common bind DN / username formats for this account name}
        {--ou= : Optional OU DN to include when guessing bind names, for example ou=IT,dc=sabz,dc=local}
        {--tls : Use LDAPS / TLS}
        {--starttls : Use STARTTLS}
        {--timeout=5 : LDAP timeout seconds}
        {--show-config : Print the sanitized settings being tested}';

    protected $description = 'Test saved or ad-hoc LDAP connection settings with sanitized diagnostics.';

    public function handle(LdapSyncService $sync, LdapConnectionFactory $connections): int
    {
        $tenantId = $this->argument('tenant');
        $settings = $this->option('host')
            ? $this->adHocSettings()
            : $this->savedSettings($sync, $tenantId);

        if ($settings === null) {
            return self::FAILURE;
        }

        $connectionName = $tenantId ?: 'manual';
        $context = $connections->logContext($connectionName, $settings);

        if ($this->option('show-config')) {
            $this->components->info('Testing LDAP settings:');
            $this->line(json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        if ($this->option('guess-user')) {
            return $this->testBindVariants($connections, $connectionName, $settings);
        }

        try {
            $connections->test($connectionName, $settings);
        } catch (Throwable $exception) {
            $this->components->error('LDAP connection test failed: '.$exception->getMessage());
            $this->line('Check storage/logs/laravel.log for the sanitized host/base DN/bind DN context.');

            return self::FAILURE;
        }

        $this->components->info('LDAP connection test passed.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function savedSettings(LdapSyncService $sync, ?string $tenantId): ?array
    {
        if (! filled($tenantId)) {
            $this->components->error('Provide a tenant ID or use --host for an ad-hoc test.');

            return null;
        }

        if (! Tenant::query()->whereKey($tenantId)->exists()) {
            $this->components->error("Tenant [{$tenantId}] was not found.");

            return null;
        }

        return $sync->settingsForTenant($tenantId)['connection'];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function testBindVariants(LdapConnectionFactory $connections, string $connectionName, array $settings): int
    {
        $candidates = $this->bindCandidates(
            (string) $this->option('guess-user'),
            (string) ($settings['base_dn'] ?? ''),
            $this->option('ou') ? (string) $this->option('ou') : null,
        );

        $this->components->info('Trying '.count($candidates).' bind username variants. Password will not be printed.');

        foreach ($candidates as $candidate) {
            $candidateSettings = array_replace($settings, ['username' => $candidate]);
            $this->line('Testing: '.$candidate);

            try {
                $connections->test($connectionName.'_'.md5($candidate), $candidateSettings);
            } catch (Throwable $exception) {
                $this->line('  Failed: '.$exception->getMessage());

                continue;
            }

            $this->components->info('LDAP connection test passed with username: '.$candidate);

            return self::SUCCESS;
        }

        $this->components->error('All bind variants failed. Check password, account lockout/disabled state, and the exact DN of the bind account.');

        return self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function bindCandidates(string $user, string $baseDn, ?string $ouDn): array
    {
        $domainParts = collect(explode(',', $baseDn))
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => str_starts_with(strtolower($part), 'dc='))
            ->map(fn (string $part): string => substr($part, 3))
            ->values();

        $dnsDomain = $domainParts->implode('.');
        $netbiosDomain = strtoupper((string) $domainParts->first());

        return collect([
            $this->option('username') ?: null,
            "cn={$user},{$baseDn}",
            "uid={$user},{$baseDn}",
            $ouDn ? "cn={$user},{$ouDn}" : null,
            $ouDn ? "uid={$user},{$ouDn}" : null,
            $dnsDomain !== '' ? "{$user}@{$dnsDomain}" : null,
            $netbiosDomain !== '' ? "{$netbiosDomain}\\{$user}" : null,
            $user,
        ])->filter()->unique()->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function adHocSettings(): array
    {
        return [
            'hosts' => preg_split('/[\s,]+/', (string) $this->option('host'), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            'port' => (int) $this->option('port'),
            'base_dn' => $this->option('base-dn'),
            'username' => $this->option('username'),
            'password' => $this->option('password'),
            'timeout' => (int) $this->option('timeout'),
            'use_tls' => (bool) $this->option('tls'),
            'use_starttls' => (bool) $this->option('starttls'),
        ];
    }
}
