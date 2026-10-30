<?php

namespace Database\Factories;

use App\Models\SkybaseAccount;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkybaseAccountFactory extends Factory
{
    protected $model = SkybaseAccount::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'status' => 'unconfigured'];
    }
}
