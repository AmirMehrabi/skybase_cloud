<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PayfastGateway
{
    public const METHODS = ['cc' => 'Credit card', 'ef' => 'EFT', 'dc' => 'Debit card', 'cp' => 'Capitec Pay', 'ss' => 'SnapScan', 'zp' => 'Zapper', 'ap' => 'Apple Pay', 'gp' => 'Google Pay', 'sp' => 'Samsung Pay', 'mc' => 'Mobicred', 'sc' => 'SCode', 'mp' => 'Scan to Pay', 'mt' => 'MoreTyme', 'rc' => 'Store card', 'mu' => 'Mukuru', 'ab' => 'Absa Pay', 'nd' => 'Nedbank Direct EFT', 'pf' => 'Payflex'];

    public function settings(string $tenantId): array
    {
        return array_replace(['enabled' => false, 'environment' => 'sandbox', 'label' => 'Pay with PayFast', 'methods' => [], 'sandbox' => [], 'live' => []], Setting::get('payment.payfast', [], $tenantId) ?? []);
    }

    public function ready(array $settings): bool
    {
        $credentials = $settings[$settings['environment']] ?? [];

        return $settings['enabled'] && filled($credentials['merchant_id'] ?? null) && filled($credentials['merchant_key'] ?? null) && filled($credentials['passphrase'] ?? null);
    }

    public function host(string $environment): string
    {
        return $environment === 'live' ? 'https://www.payfast.co.za' : 'https://sandbox.payfast.co.za';
    }

    public function parameterString(array $data): string
    {
        unset($data['signature']);

        return collect($data)->filter(fn ($value) => $value !== '' && $value !== null)->map(fn ($value, $key) => $key.'='.urlencode(trim((string) $value)))->implode('&');
    }

    public function signature(array $data, string $passphrase): string
    {
        return md5($this->parameterString($data).'&passphrase='.urlencode(trim($passphrase)));
    }

    public function validate(array $data, array $credentials, string $environment): bool
    {
        if (! hash_equals($this->signature($data, $credentials['passphrase']), (string) ($data['signature'] ?? '')) || (string) ($data['merchant_id'] ?? '') !== (string) $credentials['merchant_id']) {
            return false;
        }

        return trim(Http::asForm()->connectTimeout(5)->timeout(15)->withBody($this->parameterString($data), 'application/x-www-form-urlencoded')->post($this->host($environment).'/eng/query/validate')->throw()->body()) === 'VALID';
    }

    public function validSource(string $ip): bool
    {
        $addresses = Cache::remember('payfast.source-addresses', 300, function (): array {
            $addresses = [];
            foreach (['www.payfast.co.za', 'sandbox.payfast.co.za', 'w1w.payfast.co.za', 'w2w.payfast.co.za'] as $host) {
                $addresses = array_merge($addresses, gethostbynamel($host) ?: []);
            }

            return array_unique($addresses);
        });

        return in_array($ip, $addresses, true);
    }

    public function minor(string $amount): int
    {
        if (! preg_match('/^([0-9]+)(?:\.([0-9]{1,2}))?$/', $amount, $matches)) {
            throw new \InvalidArgumentException('Invalid monetary amount.');
        }

        return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }
}
