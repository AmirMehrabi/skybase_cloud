<?php

namespace Database\Factories;

use App\Models\BulkDeletionRun;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BulkDeletionRun>
 */
class BulkDeletionRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'module' => BulkDeletionRun::MODULE_SUBSCRIPTIONS,
            'action' => BulkDeletionRun::ACTION_DELETE,
            'selection_mode' => BulkDeletionRun::SELECTION_SELECTED,
            'filters' => [],
            'selected_ids' => [],
            'excluded_ids' => [],
            'status' => BulkDeletionRun::STATUS_QUEUED,
            'total_count' => 0,
            'processed_count' => 0,
            'deleted_count' => 0,
            'failed_count' => 0,
            'summary' => [],
            'error' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
