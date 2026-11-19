<?php

namespace App\Services\RouterOs;

use App\Models\Router;
use App\Services\OutboundConnectionPolicy;
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
        $host = app(OutboundConnectionPolicy::class)->resolve($router->ip_address, $port, $router->tenant_id, configuredDestination: true);
        $connection = @stream_socket_client(
            'udp://'.(str_contains($host, ':') ? '['.$host.']' : $host).':'.$port,
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

            return $this->validateResponse($response, ord($packet[1]), $requestAuthenticator, $secret);
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
        $zeroAuthenticator = str_repeat("\0", 16);
        $attributes = $this->disconnectAttributes($router, $username, $acctSessionId, $nasIpAddress, $framedIpAddress);
        $placeholder = $this->encodeAttribute(80, $zeroAuthenticator);
        $header = pack('CCn', 40, $identifier, 20 + strlen($attributes.$placeholder));
        $messageAuthenticator = hash_hmac('md5', $header.$zeroAuthenticator.$attributes.$placeholder, $secret, true);
        $attributes .= $this->encodeAttribute(80, $messageAuthenticator);
        $requestAuthenticator = hash('md5', $header.$zeroAuthenticator.$attributes.$secret, true);

        return [$header.$requestAuthenticator.$attributes, $requestAuthenticator];
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

    private function validateResponse(string $response, int $requestIdentifier, string $requestAuthenticator, string $secret): int
    {
        if (strlen($response) < 20) {
            throw new RuntimeException('RouterOS CoA response was incomplete.');
        }

        $code = ord($response[0]);
        $identifier = ord($response[1]);
        $length = unpack('n', substr($response, 2, 2))[1];

        if ($identifier !== $requestIdentifier || $length < 20) {
            throw new RuntimeException('RouterOS CoA response header is invalid.');
        }

        if ($length > strlen($response)) {
            throw new RuntimeException('RouterOS CoA response was truncated.');
        }

        $authenticator = substr($response, 4, 16);
        $attributes = substr($response, 20, $length - 20);
        $expectedAuthenticator = hash('md5', chr($code).chr($identifier).pack('n', $length).$requestAuthenticator.$attributes.$secret, true);

        if (! hash_equals($expectedAuthenticator, $authenticator)) {
            throw new RuntimeException('RouterOS CoA response failed validation.');
        }

        $messageAuthenticator = null;
        $errorCause = null;
        for ($offset = 0; $offset < strlen($attributes); $offset += $attributeLength) {
            if ($offset + 2 > strlen($attributes)) {
                throw new RuntimeException('RouterOS CoA response contains an incomplete attribute.');
            }

            $type = ord($attributes[$offset]);
            $attributeLength = ord($attributes[$offset + 1]);
            if ($attributeLength < 2 || $offset + $attributeLength > strlen($attributes)) {
                throw new RuntimeException('RouterOS CoA response contains an invalid attribute.');
            }

            if ($type === 80) {
                if ($attributeLength !== 18 || $messageAuthenticator !== null) {
                    throw new RuntimeException('RouterOS CoA response Message-Authenticator is invalid.');
                }

                $messageAuthenticator = substr($attributes, $offset + 2, 16);
                $attributesForMac = substr_replace($attributes, str_repeat("\0", 16), $offset + 2, 16);
                $mac = hash_hmac('md5', substr($response, 0, 4).$requestAuthenticator.$attributesForMac, $secret, true);
                if (! hash_equals($mac, $messageAuthenticator)) {
                    throw new RuntimeException('RouterOS CoA response Message-Authenticator failed validation.');
                }
            } elseif ($type === 101 && $attributeLength === 6) {
                $errorCause = unpack('N', substr($attributes, $offset + 2, 4))[1];
            }
        }

        if ($code === 41) {
            return 1;
        }

        if ($code === 42) {
            $message = $this->replyMessage($attributes) ?? 'RouterOS rejected the CoA disconnect request.';

            throw new RuntimeException($errorCause === null ? $message : $message.' Error-Cause '.$errorCause.'.');
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
