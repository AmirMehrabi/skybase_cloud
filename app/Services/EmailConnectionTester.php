<?php

namespace App\Services;

use RuntimeException;

class EmailConnectionTester
{
    public function test(array $settings, string $direction): void
    {
        if ($direction === 'outgoing') {
            $transport = app(TenantMailService::class)->transport($settings);
            try {
                $transport->start();
            } finally {
                $transport->stop();
            }

            return;
        }
        $host = (string) ($settings['host'] ?? '');
        $port = (int) ($settings['port'] ?? 993);
        $resolved = app(OutboundConnectionPolicy::class)->resolve($host, $port, configuredDestination: true);
        $encryption = $settings['encryption'] ?? 'ssl';
        $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
        $address = ($encryption === 'ssl' ? 'ssl' : 'tcp').'://'.(str_contains($resolved, ':') ? '['.$resolved.']' : $resolved).':'.$port;
        $socket = @stream_socket_client($address, $code, $error, 10, STREAM_CLIENT_CONNECT, $context);
        if (! $socket) {
            throw new RuntimeException('Unable to connect to the incoming email server.');
        }
        stream_set_timeout($socket, 10);
        try {
            $imap = ($settings['protocol'] ?? 'imap') === 'imap';
            $greeting = $this->line($socket);
            if (! str_starts_with(strtoupper($greeting), $imap ? '* OK' : '+OK')) {
                throw new RuntimeException('The server did not return a valid mailbox greeting.');
            }
            if ($encryption === 'tls') {
                $this->command($socket, $imap ? 'a1 STARTTLS' : 'STLS', $imap ? 'a1' : null);
                if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                    throw new RuntimeException('Incoming email TLS negotiation failed.');
                }
            }
            $username = (string) ($settings['username'] ?? '');
            $password = (string) ($settings['password'] ?? '');
            if ($username === '' || $password === '' || preg_match('/[\r\n\x00]/', $username.$password)) {
                throw new RuntimeException('A valid mailbox username and password are required.');
            }
            if ($imap) {
                $quote = fn (string $value): string => '"'.addcslashes($value, '\\"').'"';
                $this->command($socket, 'a2 LOGIN '.$quote($username).' '.$quote($password), 'a2');
                $this->command($socket, 'a3 EXAMINE '.$quote((string) ($settings['mailbox'] ?? 'INBOX')), 'a3');
                fwrite($socket, "a4 LOGOUT\r\n");
            } else {
                $this->command($socket, 'USER '.$username);
                $this->command($socket, 'PASS '.$password);
                $this->command($socket, 'STAT');
                fwrite($socket, "QUIT\r\n");
            }
        } finally {
            fclose($socket);
        }
    }

    protected function line(mixed $socket): string
    {
        $line = fgets($socket, 8192);
        if ($line === false) {
            throw new RuntimeException('The mailbox server closed the connection or timed out.');
        }

        return trim($line);
    }

    protected function command(mixed $socket, string $command, ?string $tag = null): void
    {
        fwrite($socket, $command."\r\n");
        for ($index = 0; $index < 200; $index++) {
            $line = $this->line($socket);
            if ($tag !== null && ! str_starts_with($line, $tag.' ')) {
                continue;
            }
            if (! str_starts_with(strtoupper($line), $tag !== null ? strtoupper($tag).' OK' : '+OK')) {
                throw new RuntimeException('Mailbox authentication or command was rejected. Check credentials, encryption and mailbox.');
            }

            return;
        }
        throw new RuntimeException('Mailbox server returned an unexpected response.');
    }
}
