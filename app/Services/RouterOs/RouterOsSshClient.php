<?php

namespace App\Services\RouterOs;

use App\Models\Router;
use App\Services\OutboundConnectionPolicy;
use App\Services\SafeRouterUsername;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class RouterOsSshClient
{
    /**
     * Disconnect active PPP sessions for the given username using SSH.
     */
    public function disconnect(Router $router, string $username, ?int $timeoutSeconds = null): int
    {
        $host = app(OutboundConnectionPolicy::class)->resolve((string) $router->ip_address, (int) ($router->ssh_port ?: 22), $router->tenant_id, configuredDestination: true);
        $port = (int) ($router->ssh_port ?: 22);
        $timeout = max(1, (int) ($timeoutSeconds ?? $router->timeout ?? 30));

        if ($host === '') {
            throw new RuntimeException('Router SSH host is not configured.');
        }

        if (! $router->api_username) {
            throw new RuntimeException('Router SSH username is not configured.');
        }

        $remoteCommand = sprintf(
            ':local removed 0; :foreach i in=[/ppp active find where name=%s] do={/ppp active remove $i; :set removed ($removed + 1)}; :put $removed',
            $this->quoteRouterosString($username),
        );

        $sshCommand = [
            'ssh',
            '-o', 'BatchMode=yes',
            ...(config('security.strict_ssh_host_keys') ? ['-o', 'StrictHostKeyChecking=yes', '-o', 'HostKeyAlias='.$router->ip_address] : []),
            '-o', 'ConnectTimeout='.$timeout,
            '-p', (string) $port,
            '-l', (string) $router->api_username,
            '--',
            $host,
            $remoteCommand,
        ];

        if (strlen((string) $router->api_username) > 255 || preg_match('/[\x00-\x1f\x7f]/', (string) $router->api_username)) {
            throw new RuntimeException('Invalid RouterOS SSH account name.');
        }
        $process = new Process($sshCommand, timeout: $timeout + 5);
        try {
            $process->mustRun();
        } catch (ProcessFailedException|ProcessTimedOutException $exception) {
            throw new RuntimeException('RouterOS SSH operation failed or timed out.', previous: $exception);
        }

        return $this->countRemovedSessions($process->getOutput());
    }

    private function quoteRouterosString(string $value): string
    {
        return SafeRouterUsername::quote($value);
    }

    private function countRemovedSessions(string $output): int
    {
        $trimmed = trim($output);

        if ($trimmed === '') {
            return 1;
        }

        if (is_numeric($trimmed)) {
            return max(0, (int) $trimmed);
        }

        return 1;
    }
}
