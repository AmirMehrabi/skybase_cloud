<?php

namespace App\Services;

use InvalidArgumentException;

class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function secret(): string
    {
        $bits = '';
        foreach (unpack('C*', random_bytes(20)) as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }
        $secret = '';
        foreach (str_split($bits, 5) as $chunk) {
            $secret .= self::ALPHABET[bindec($chunk)];
        }

        return $secret;
    }

    public function code(string $secret, int $counter, int $digits = 6): string
    {
        if (! preg_match('/^[A-Z2-7]+$/D', $secret) || ! in_array($digits, [6, 8], true) || $counter < 0) {
            throw new InvalidArgumentException('Invalid TOTP parameters.');
        }
        $bits = '';
        foreach (str_split($secret) as $letter) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $letter)), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $key .= chr(bindec($chunk));
            }
        }
        $digest = hash_hmac('sha1', pack('N2', intdiv($counter, 4294967296), $counter % 4294967296), $key, true);
        $offset = ord($digest[19]) & 15;
        $number = unpack('N', substr($digest, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($number % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public function matchingCounter(string $secret, string $code, ?int $lastCounter = null): ?int
    {
        if (! preg_match('/^[0-9]{6}$/D', $code)) {
            return null;
        }
        $current = intdiv(now()->timestamp, 30);
        foreach ([$current, $current - 1, $current + 1] as $counter) {
            if ($counter > ($lastCounter ?? -1) && hash_equals($this->code($secret, $counter), $code)) {
                return $counter;
            }
        }

        return null;
    }
}
