<?php

namespace App\Services\RouterOs;

use App\Models\Router;
use RuntimeException;

class RouterOsSshClient
{
    /**
     * Disconnect active PPP sessions for the given username using SSH.
     */
    public function disconnect(Router $router, string $username, ?int $timeoutSeconds = null): int
    {
        $host = (string) $router->ip_address;
        $port = (int) ($router->ssh_port ?: 22);
        $timeout = max(1, (int) ($timeoutSeconds ?? $router->timeout ?? 30));

        if ($host === '') {
            throw new RuntimeException('Router SSH host is not configured.');
        }

        if (! $router->api_username) {
            throw new RuntimeException('Router SSH username is not configured.');
        }

        $remoteCommand = sprintf(
            ':foreach i in=[/ppp active find where name=%s] do={/ppp active remove $i}',
            $this->quoteRouterosString($username),
        );

        $sshCommand = [
            'ssh',
            '-o', 'BatchMode=yes',
            '-o', 'ConnectTimeout='.$timeout,
            '-p', (string) $port,
            $router->api_username.'@'.$host,
            $remoteCommand,
        ];

        $process = $this->openProcess($sshCommand);

        if (! is_resource($process['process'])) {
            throw new RuntimeException('Unable to start SSH process for RouterOS disconnect.');
        }

        $output = stream_get_contents($process['pipes'][1]) ?: '';
        $errorOutput = stream_get_contents($process['pipes'][2]) ?: '';

        foreach ($process['pipes'] as $pipe) {
            fclose($pipe);
        }

        $exitCode = proc_close($process['process']);

        if ($exitCode !== 0) {
            $message = trim($errorOutput ?: $output);

            throw new RuntimeException($message !== '' ? $message : 'RouterOS SSH disconnect command failed.');
        }

        return $this->countRemovedSessions($output);
    }

    /**
     * @param  array<int, string>  $sshCommand
     * @return array{process: resource, pipes: array<int, resource>}
     */
    protected function openProcess(array $sshCommand): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($sshCommand, $descriptorSpec, $pipes);

        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start SSH process for RouterOS disconnect.');
        }

        fclose($pipes[0]);

        return [
            'process' => $process,
            'pipes' => [
                1 => $pipes[1],
                2 => $pipes[2],
            ],
        ];
    }

    private function quoteRouterosString(string $value): string
    {
        return '"'.$value.'"';
    }

    private function countRemovedSessions(string $output): int
    {
        if (preg_match_all('/removed/i', $output) > 0) {
            return preg_match_all('/removed/i', $output);
        }

        return 1;
    }
}
