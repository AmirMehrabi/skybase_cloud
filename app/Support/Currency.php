<?php

namespace App\Support;

final class Currency
{
    public const RIAL_SIGN = '﷼';

    public static function label(?string $currency = null, ?string $fallback = null): string
    {
        $currency ??= tenant()?->currency ?? 'USD';

        return strtoupper($currency) === 'IRR' ? self::RIAL_SIGN : ($fallback ?? $currency);
    }
}
