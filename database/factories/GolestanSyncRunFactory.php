<?php

namespace Database\Factories;

use App\Models\GolestanSyncRun;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<GolestanSyncRun> */
class GolestanSyncRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => tenant_id() ?? Tenant::factory(),
            'preview_token' => (string) Str::uuid(),
            'status' => 'completed',
            'total' => 1,
            'outcomes' => [['status' => 'skipped', 'message' => 'Demo student was skipped.']],
        ];
    }
}
