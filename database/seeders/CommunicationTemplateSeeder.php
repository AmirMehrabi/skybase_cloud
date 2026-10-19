<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\NotificationAutomationService;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class CommunicationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->each(function (Tenant $tenant): void {
            app(TenantContext::class)->run($tenant->id, function () use ($tenant): void {
                app(NotificationAutomationService::class)->ensureDefaults($tenant->id);
            });
        });
    }
}
