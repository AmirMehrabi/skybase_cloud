<?php

namespace App\Services;

use InvalidArgumentException;

class SkybasePackageCatalog
{
    /** @return array<string, array{name: string, subscriber_limit: int, monthly_price_minor: int, features: list<string>, self_service: bool}> */
    public function all(): array
    {
        return config('skybase_billing.packages');
    }

    /** @return array{name: string, subscriber_limit: int, monthly_price_minor: int, features: list<string>, self_service: bool} */
    public function get(string $key): array
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException('Unknown SkyBase package.');
    }

    public function recommendedKey(int $subscribers): ?string
    {
        if ($subscribers < 0) {
            throw new InvalidArgumentException('Subscriber count cannot be negative.');
        }

        foreach ($this->all() as $key => $package) {
            if ($subscribers <= $package['subscriber_limit']) {
                return $key;
            }
        }

        return null;
    }

    /** @return list<array{name: string, limit: int, price: int}> */
    public function pricingCalculatorPlans(): array
    {
        return array_values(array_map(fn (array $package): array => [
            'name' => $package['name'],
            'limit' => $package['subscriber_limit'],
            'price' => intdiv($package['monthly_price_minor'], 100),
        ], $this->all()));
    }
}
