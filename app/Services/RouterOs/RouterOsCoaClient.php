<?php

namespace App\Services\RouterOs;

use App\Models\Router;
use RuntimeException;

class RouterOsCoaClient
{
    /**
     * Send a RouterOS CoA Disconnect-Request for the given PPP session.
     */
    public function disconnect(
        Router $router,
        string $username,
        ?string $acctSessionId = null,
        ?string $nasIpAddress = null,
        ?string $framedIpAddress = null,
        ?int $timeoutSeconds = null,
    ): int {
        $secret = trim((string) $router->coa_secret);

        if ($secret === '') {
            $secret = trim((string) data_get($router, 'nas_secret'));
        }

        if ($secret === '') {
            throw new RuntimeException('RouterOS CoA secret is not configured.');
        }

        $port = (int) ($router->coa_port ?: 1700);
        $timeout = max(1, min((int) ($timeoutSeconds ?? $router->timeout ?? 5), 5));
        $connection = @stream_socket_client(
            "udp://{$router->ip_address}:{$port}",
            $errno,
            $error,
            $timeout,
            STREAM_CLIENT_CONNECT,
        );

        if (! $connection) {
            throw new RuntimeException("Unable to connect to RouterOS CoA endpoint: {$error}", $errno);
        }

        stream_set_timeout($connection, $timeout);

        try {
            [$packet, $requestAuthenticator] = $this->buildDisconnectRequest(
                $router,
                $username,
                $acctSessionId,
                $nasIpAddress,
                $framedIpAddress,
                $secret,
            );

            $written = fwrite($connection, $packet);

            if ($written === false || $written !== strlen($packet)) {
                throw new RuntimeException('RouterOS CoA request could not be sent.');
            }

            $response = fread($connection, 4096);

            if ($response === false || $response === '') {
                $meta = stream_get_meta_data($connection);

                if ($meta['timed_out'] ?? false) {
                    throw new RuntimeException('RouterOS CoA request timed out.');
                }

                throw new RuntimeException('RouterOS CoA endpoint returned no response.');
            }

            return $this->validateResponse($response, $requestAuthenticator, $secret);
        } finally {
            fclose($connection);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function buildDisconnectRequest(
        Router $router,
        string $username,
        ?string $acctSessionId,
        ?string $nasIpAddress,
        ?string $framedIpAddress,
        string $secret,
    ): array {
        $identifier = random_int(0, 255);
        $requestAuthenticator = random_bytes(16);
        $attributes = $this->disconnectAttributes($router, $username, $acctSessionId, $nasIpAddress, $framedIpAddress);

        $attributesWithPlaceholder = $attributes.$this->encodeAttribute(80, str_repeat("\0", 16));
        $packet = pack('CCn', 40, $identifier, 20 + strlen($attributesWithPlaceholder)).$requestAuthenticator.$attributesWithPlaceholder;
        $messageAuthenticator = hash_hmac('md5', $packet, $secret, true);
        $attributes = $attributes.$this->encodeAttribute(80, $messageAuthenticator);

        $finalPacket = pack('CCn', 40, $identifier, 20 + strlen($attributes)).$requestAuthenticator.$attributes;

        return [$finalPacket, $requestAuthenticator];
    }

    private function disconnectAttributes(
        Router $router,
        string $username,
        ?string $acctSessionId,
        ?string $nasIpAddress,
        ?string $framedIpAddress,
    ): string {
        $attributes = $this->encodeAttribute(1, $username);
        if (filled($acctSessionId)) {
            $attributes .= $this->encodeAttribute(44, $acctSessionId);
        }

        $nasIpAddress = filled($nasIpAddress) ? $nasIpAddress : $router->ip_address;

        if (blank($nasIpAddress)) {
            throw new RuntimeException('RouterOS CoA NAS-IP-Address is not configured.');
        }

        $attributes .= $this->encodeIpAttribute(4, $nasIpAddress);

        if (filled($framedIpAddress)) {
            $attributes .= $this->encodeIpAttribute(8, $framedIpAddress);
        }

        return $attributes;
    }

    private function encodeAttribute(int $type, string $value): string
    {
        return pack('CC', $type, strlen($value) + 2).$value;
    }

    private function encodeIpAttribute(int $type, string $ipAddress): string
    {
        $packed = @inet_pton($ipAddress);

        if ($packed === false) {
            throw new RuntimeException("Invalid IP address provided for RouterOS CoA packet: {$ipAddress}");
        }

        return $this->encodeAttribute($type, $packed);
    }

    private function validateResponse(string $response, string $requestAuthenticator, string $secret): int
    {
        if (strlen($response) < 20) {
            throw new RuntimeException('RouterOS CoA response was incomplete.');
        }

        $code = ord($response[0]);
        $identifier = ord($response[1]);
        $length = unpack('n', substr($response, 2, 2))[1];

        if ($length > strlen($response)) {
            throw new RuntimeException('RouterOS CoA response was truncated.');
        }

        $authenticator = substr($response, 4, 16);
        $attributes = substr($response, 20, $length - 20);
        $expectedAuthenticator = hash('md5', chr($code).chr($identifier).pack('n', $length).$requestAuthenticator.$attributes.$secret, true);

        if (! hash_equals($expectedAuthenticator, $authenticator)) {
            throw new RuntimeException('RouterOS CoA response failed validation.');
        }

        if ($code === 41) {
            return 1;
        }

        if ($code === 42) {
            throw new RuntimeException($this->replyMessage($attributes) ?? 'RouterOS rejected the CoA disconnect request.');
        }

        throw new RuntimeException('Unexpected RouterOS CoA response received.');
    }

    private function replyMessage(string $attributes): ?string
    {
        $offset = 0;

        while ($offset + 2 <= strlen($attributes)) {
            $type = ord($attributes[$offset]);
            $length = ord($attributes[$offset + 1]);

            if ($length < 2 || $offset + $length > strlen($attributes)) {
                break;
            }

            $value = substr($attributes, $offset + 2, $length - 2);

            if ($type === 18) {
                return $value;
            }

            $offset += $length;
        }

        return null;
    }
}
