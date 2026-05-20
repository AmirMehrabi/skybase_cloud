<?php

namespace App\Services\RouterOs;

use App\Models\Router;
use RuntimeException;

class RouterOsTrafficFlowService
{
    /**
     * @return array<string, mixed>
     */
    public function configure(Router $router): array
    {
        $connection = $this->connect($router);

        try {
            $this->login($connection, (string) $router->api_username, (string) $router->api_password);

            $this->writeSentence($connection, [
                '/ip/traffic-flow/set',
                '=enabled='.($router->netflow_enabled ? 'yes' : 'no'),
                '=interfaces='.($router->netflow_interfaces ?: 'all'),
            ]);
            $this->readResponse($connection);

            if ($router->netflow_enabled) {
                $this->upsertTarget($connection, $router);
            }

            return ['ok' => true];
        } finally {
            fclose($connection);
        }
    }

    /**
     * @return resource
     */
    private function connect(Router $router)
    {
        $host = $router->ip_address;
        $port = (int) ($router->api_port ?: 8728);
        $timeout = (int) ($router->timeout ?: 30);
        $connection = @stream_socket_client("tcp://{$host}:{$port}", $errno, $error, $timeout);

        if (! $connection) {
            throw new RuntimeException("Unable to connect to RouterOS API: {$error}", $errno);
        }

        stream_set_timeout($connection, $timeout);

        return $connection;
    }

    /**
     * @param  resource  $connection
     */
    private function login($connection, string $username, string $password): void
    {
        $this->writeSentence($connection, [
            '/login',
            '=name='.$username,
            '=password='.$password,
        ]);

        $this->readResponse($connection);
    }

    /**
     * @param  resource  $connection
     */
    private function upsertTarget($connection, Router $router): void
    {
        $this->writeSentence($connection, ['/ip/traffic-flow/target/print']);
        $targets = $this->readResponse($connection);
        $targetId = $this->matchingTargetId($targets, (string) $router->netflow_collector_host, (int) $router->netflow_collector_port);
        $sentence = [
            $targetId ? '/ip/traffic-flow/target/set' : '/ip/traffic-flow/target/add',
            '=dst-address='.$router->netflow_collector_host,
            '=port='.(string) $router->netflow_collector_port,
            '=version='.(string) $router->netflow_version,
            '=enabled=yes',
        ];

        if ($targetId) {
            $sentence[] = '=.id='.$targetId;
        }

        $this->writeSentence($connection, $sentence);
        $this->readResponse($connection);
    }

    /**
     * @param  array<int, array<string, string>>  $targets
     */
    private function matchingTargetId(array $targets, string $host, int $port): ?string
    {
        foreach ($targets as $target) {
            if (($target['dst-address'] ?? null) === $host && (int) ($target['port'] ?? 0) === $port) {
                return $target['.id'] ?? null;
            }
        }

        return null;
    }

    /**
     * @param  resource  $connection
     * @param  array<int, string>  $words
     */
    private function writeSentence($connection, array $words): void
    {
        foreach ($words as $word) {
            fwrite($connection, $this->encodeLength(strlen($word)).$word);
        }

        fwrite($connection, chr(0));
    }

    /**
     * @param  resource  $connection
     * @return array<int, array<string, string>>
     */
    private function readResponse($connection): array
    {
        $rows = [];
        $current = [];

        while (true) {
            $word = $this->readWord($connection);

            if ($word === '') {
                continue;
            }

            if ($word === '!done') {
                if ($current !== []) {
                    $rows[] = $current;
                }

                return $rows;
            }

            if ($word === '!re') {
                if ($current !== []) {
                    $rows[] = $current;
                }

                $current = [];

                continue;
            }

            if ($word === '!trap') {
                $trap = $this->readTrap($connection);

                throw new RuntimeException($trap['message'] ?? 'RouterOS API returned an error.');
            }

            if (str_starts_with($word, '=')) {
                [$key, $value] = explode('=', substr($word, 1), 2) + [1 => ''];
                $current[$key] = $value;
            }
        }
    }

    /**
     * @param  resource  $connection
     * @return array<string, string>
     */
    private function readTrap($connection): array
    {
        $trap = [];

        while (($word = $this->readWord($connection)) !== '!done') {
            if (str_starts_with($word, '=')) {
                [$key, $value] = explode('=', substr($word, 1), 2) + [1 => ''];
                $trap[$key] = $value;
            }
        }

        return $trap;
    }

    /**
     * @param  resource  $connection
     */
    private function readWord($connection): string
    {
        $length = $this->decodeLength($connection);

        if ($length === 0) {
            return '';
        }

        $word = fread($connection, $length);

        if ($word === false || strlen($word) !== $length) {
            throw new RuntimeException('RouterOS API response was incomplete.');
        }

        return $word;
    }

    /**
     * @param  resource  $connection
     */
    private function decodeLength($connection): int
    {
        $byte = fread($connection, 1);

        if ($byte === false || $byte === '') {
            throw new RuntimeException('RouterOS API connection closed unexpectedly.');
        }

        $length = ord($byte);

        if (($length & 0x80) === 0x00) {
            return $length;
        }

        if (($length & 0xC0) === 0x80) {
            return (($length & ~0xC0) << 8) + ord((string) fread($connection, 1));
        }

        if (($length & 0xE0) === 0xC0) {
            return (($length & ~0xE0) << 16) + unpack('n', (string) fread($connection, 2))[1];
        }

        throw new RuntimeException('Unsupported RouterOS API word length.');
    }

    private function encodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        if ($length < 0x4000) {
            return chr(($length >> 8) | 0x80).chr($length & 0xFF);
        }

        if ($length < 0x200000) {
            return chr(($length >> 16) | 0xC0).chr(($length >> 8) & 0xFF).chr($length & 0xFF);
        }

        throw new RuntimeException('RouterOS API word is too long.');
    }
}
