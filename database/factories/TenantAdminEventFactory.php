<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantAdminEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantAdminEventFactory extends Factory
{
    protected $model = TenantAdminEvent::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'event' => 'owner.note', 'reason' => fake()->sentence(), 'changes' => []];
    }
}
