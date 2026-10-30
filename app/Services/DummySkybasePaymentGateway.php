<?php

namespace App\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class DummySkybasePaymentGateway
{
    /** @return array{reference: string, invoice_id: string, amount_minor: int, currency: string, simulated: bool} */
    public function simulate(string $invoiceId, int $amountMinor, string $currency): array
    {
        if (! config('skybase_billing.enabled') || ! config('skybase_billing.dummy_gateway_enabled') || app()->environment('production')) {
            throw new LogicException('Simulated payments are disabled.');
        }

        if ($invoiceId === '' || $amountMinor <= 0 || $currency !== config('skybase_billing.currency')) {
            throw new InvalidArgumentException('Invalid simulated payment terms.');
        }

        return [
            'reference' => 'dummy-'.Str::uuid(),
            'invoice_id' => $invoiceId,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'simulated' => true,
        ];
    }
}
