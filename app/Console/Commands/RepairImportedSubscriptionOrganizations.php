<?php

namespace App\Console\Commands;

use App\Services\UserGroupAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RepairImportedSubscriptionOrganizations extends Command
{
    protected $signature = 'skybase:repair-imported-subscription-organizations
                            {--tenant-id= : Tenant UUID whose imported subscriptions will be repaired}
                            {--dry-run : Report changes without writing them}
                            {--force : Skip confirmation}';

    protected $description = 'Restore legacy organization ownership on imported subscriptions and dependent records';

    public function handle(UserGroupAssignmentService $assignments): int
    {
        $tenantId = trim((string) $this->option('tenant-id'));
        $dryRun = (bool) $this->option('dry-run');

        if ($tenantId === '') {
            $this->error('--tenant-id is required.');

            return self::FAILURE;
        }

        foreach (['subscriptions', 'organizations', 'skybase_legacy_migration_maps', 'skybase_legacy_migration_archives'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Required table {$table} does not exist.");

                return self::FAILURE;
            }
        }

        if (! Schema::hasColumn('subscriptions', 'organization_id')) {
            $this->error('subscriptions.organization_id does not exist. Run php artisan migrate first.');

            return self::FAILURE;
        }

        if (! DB::table('tenants')->where('id', $tenantId)->exists()) {
            $this->error("Tenant {$tenantId} does not exist.");

            return self::FAILURE;
        }

        $archives = $this->subscriptionArchives($tenantId);
        $total = (clone $archives)->count();

        if ($total === 0) {
            $this->warn('No archived legacy subscriptions were found for this tenant.');

            return self::SUCCESS;
        }

        $this->table(['Setting', 'Value'], [
            ['Tenant', $tenantId],
            ['Archived subscriptions', $total],
            ['Mode', $dryRun ? 'DRY RUN' : 'LIVE'],
        ]);

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Repair organization ownership for these imported subscriptions?')) {
            $this->info('Repair cancelled.');

            return self::SUCCESS;
        }

        $counts = ['updated' => 0, 'unchanged' => 0, 'missing' => 0, 'failed' => 0];

        $archives->orderBy('id')->chunkById(250, function ($rows) use ($assignments, $tenantId, $dryRun, &$counts): void {
            foreach ($rows as $archive) {
                try {
                    $payload = json_decode($archive->payload, true, 512, JSON_THROW_ON_ERROR);
                    $legacyOrganizationId = $payload['organization_id'] ?? null;

                    if (blank($legacyOrganizationId)) {
                        $counts['missing']++;

                        continue;
                    }

                    $subscriptionId = DB::table('skybase_legacy_migration_maps')
                        ->where('fingerprint', $archive->fingerprint)
                        ->where('source_table', 'subscriptions')
                        ->where('source_key_hash', $archive->source_key_hash)
                        ->where('status', 'mapped')
                        ->value('target_key');
                    $organizationKey = json_encode(['id' => $legacyOrganizationId], JSON_THROW_ON_ERROR);
                    $organizationId = DB::table('skybase_legacy_migration_maps')
                        ->where('fingerprint', $archive->fingerprint)
                        ->where('source_table', 'organizations')
                        ->where('source_key_hash', hash('sha256', $organizationKey))
                        ->where('status', 'mapped')
                        ->value('target_key');

                    if (blank($subscriptionId) || blank($organizationId)) {
                        $counts['missing']++;

                        continue;
                    }

                    $organization = DB::table('organizations')
                        ->where('tenant_id', $tenantId)
                        ->where('id', $organizationId)
                        ->first(['id', 'user_group_id']);
                    $subscription = DB::table('subscriptions')
                        ->where('tenant_id', $tenantId)
                        ->where('id', $subscriptionId)
                        ->first(['id', 'organization_id', 'user_group_id']);

                    if ($organization === null || $subscription === null) {
                        $counts['missing']++;

                        continue;
                    }

                    if ((string) $subscription->organization_id === (string) $organization->id
                        && (string) $subscription->user_group_id === (string) $organization->user_group_id) {
                        $counts['unchanged']++;

                        continue;
                    }

                    if (! $dryRun) {
                        $assignments->assignSubscriptionOrganization(
                            (int) $subscription->id,
                            $tenantId,
                            (int) $organization->id,
                            $organization->user_group_id === null ? null : (int) $organization->user_group_id,
                        );
                    }

                    $counts['updated']++;
                } catch (Throwable $exception) {
                    $counts['failed']++;
                    $this->error("Archive row {$archive->id}: {$exception->getMessage()}");
                }
            }
        });

        $this->table(['Updated', 'Unchanged', 'Missing relationship', 'Failed'], [[
            $counts['updated'],
            $counts['unchanged'],
            $counts['missing'],
            $counts['failed'],
        ]]);

        if ($counts['missing'] > 0) {
            $this->warn('Some rows had no legacy organization or no corresponding migration map; they were left unchanged.');
        }

        if ($counts['failed'] > 0) {
            return self::FAILURE;
        }

        $this->info($dryRun ? 'Dry run completed; no data was changed.' : 'Imported subscription organization ownership repaired.');

        return self::SUCCESS;
    }

    private function subscriptionArchives(string $tenantId): Builder
    {
        return DB::table('skybase_legacy_migration_archives')
            ->where('tenant_id', $tenantId)
            ->where('source_table', 'subscriptions');
    }
}
