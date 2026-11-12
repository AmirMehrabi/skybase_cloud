<?php

namespace Database\Factories;

use App\Models\GatewayPaymentAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GatewayPaymentAttempt>
 */
class GatewayPaymentAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => (string) Str::uuid(),
            'environment' => 'sandbox',
            'currency' => 'ZAR',
            'amount_minor' => 10000,
            'credentials' => ['merchant_id' => '10000100', 'merchant_key' => 'test-key', 'passphrase' => 'test-pass'],
        ];
    }
}
