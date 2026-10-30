<?php

namespace Database\Factories;

use App\Models\SkybaseInvoice;
use App\Models\SkybasePaymentAttempt;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SkybasePaymentAttemptFactory extends Factory
{
    protected $model = SkybasePaymentAttempt::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'skybase_invoice_id' => fn (array $attributes) => SkybaseInvoice::factory()->create(['tenant_id' => $attributes['tenant_id']])->id, 'authority' => Str::random(48), 'expires_at' => now()->addMinutes(30)];
    }
}
