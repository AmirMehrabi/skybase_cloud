<?php

namespace Database\Seeders;

use App\Models\GolestanStudentLink;
use App\Models\GolestanSyncRun;
use App\Models\User;
use Illuminate\Database\Seeder;

class GolestanSyncSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = tenant_id();
        if ($tenantId === null || ! app()->environment('local', 'testing')) {
            return;
        }

        $owner = User::query()->where('tenant_id', $tenantId)->where('role', 'owner')->where('status', 'active')->first();
        if ($owner === null) {
            return;
        }

        $link = GolestanStudentLink::factory()->create(['tenant_id' => $tenantId]);
        GolestanSyncRun::factory()->create([
            'tenant_id' => $tenantId,
            'user_id' => $owner->id,
            'outcomes' => [['status' => 'synced', 'message' => 'Created demo customer.', 'customer_id' => $link->customer_id]],
        ]);
    }
}
