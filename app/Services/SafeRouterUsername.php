<?php

namespace App\Services;

use InvalidArgumentException;

class SafeRouterUsername
{
    public const RULE = 'regex:/\A[a-zA-Z0-9_.@+\\-]+\z/D';

    public static function validationRule(): string
    {
        return config('security.strict_router_usernames') ? self::RULE : 'regex:/\A[^\x00-\x1f\x7f]+\z/D';
    }

    public static function quote(string $value): string
    {
        if ($value === '' || strlen($value) > 255 || preg_match('/[\x00-\x1f\x7f]/', $value)) {
            throw new InvalidArgumentException('Invalid router username.');
        }

        return '"'.strtr($value, ['\\' => '\\\\', '"' => '\\"', '$' => '\\$']).'"';
    }
}
