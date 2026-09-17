<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\SubscriptionDataAdjustment;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateTenantCurrency extends Command
{
    protected $signature = 'billing:update-currency
                            {currency : The three-letter ISO 4217 currency code to apply}
                            {--tenant-id= : Tenant UUID to update}
                            {--dry-run : Report affected records without writing changes}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Update a tenant currency code without converting monetary amounts';

    public function handle(): int
    {
        $tenantId = trim((string) $this->option('tenant-id'));
        $currency = strtoupper(trim((string) $this->argument('currency')));
        $dryRun = (bool) $this->option('dry-run');

        if ($tenantId === '') {
            $this->error('--tenant-id is required to prevent cross-tenant changes.');

            return self::FAILURE;
        }

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            $this->error('The currency must be a three-letter ISO 4217 code, such as ZAR.');

            return self::FAILURE;
        }

        if (! Tenant::query()->whereKey($tenantId)->exists()) {
            $this->error("Tenant {$tenantId} does not exist.");

            return self::FAILURE;
        }

        $updates = [
            'tenant' => Tenant::query()->whereKey($tenantId)->where('currency', '!=', $currency),
            'plans' => Plan::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('currency', '!=', $currency),
            'settings' => Setting::query()
                ->where('tenant_id', $tenantId)
                ->where('key', 'currency')
                ->where('value', '!=', json_encode($currency, JSON_THROW_ON_ERROR)),
            'subscription data adjustments' => SubscriptionDataAdjustment::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('currency')
                ->where('currency', '!=', $currency),
        ];
        $counts = collect($updates)->map(fn ($query): int => (clone $query)->count());

        $this->table(['Record type', 'Records to update'], $counts
            ->map(fn (int $count, string $type): array => [$type, $count])
            ->values()
            ->all());
        $this->line("Target currency: {$currency}");
        $this->line('Monetary amounts will not be converted or modified.');

        if ($dryRun) {
            $this->info('Dry run complete. No records were changed.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Update this tenant's currency codes to {$currency}?")) {
            $this->info('Currency update cancelled.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates, $currency): void {
            $updates['tenant']->update(['currency' => $currency]);
            $updates['plans']->update(['currency' => $currency]);
            $updates['settings']->update(['value' => json_encode($currency, JSON_THROW_ON_ERROR)]);
            $updates['subscription data adjustments']->update(['currency' => $currency]);
        });

        $this->info("Currency codes updated to {$currency}.");

        return self::SUCCESS;
    }
}
