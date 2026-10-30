<?php

namespace Database\Factories;

use App\Models\SkybaseInvoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkybaseInvoiceFactory extends Factory
{
    protected $model = SkybaseInvoice::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'package_key' => 'starter', 'amount_minor' => 6900, 'currency' => 'USD', 'subscriber_limit' => 150, 'features' => []];
    }
}
