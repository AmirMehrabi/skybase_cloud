<?php

namespace Database\Factories;

use App\Models\AcsConnection;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AcsConnection> */
class AcsConnectionFactory extends Factory
{
    protected $model = AcsConnection::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'name' => 'Test GenieACS', 'base_url' => 'http://127.0.0.1:7557'];
    }
}
