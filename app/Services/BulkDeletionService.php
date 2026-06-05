<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BulkDeletionRun;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class BulkDeletionService
{
    public function __construct(
        protected SubscriptionDeletionService $subscriptionDeletionService,
    ) {}

    /**
     * @return array{
     *     processed_count:int,
     *     deleted_count:int,
     *     failed_count:int,
     *     deleted_items: array<int, array<string, mixed>>,
     *     failed_items: array<int, array<string, mixed>>,
     *     target_count:int,
     *     module:string,
     *     action:string,
     *     selection_mode:string,
     *     filters: array<string, mixed>,
     *     selected_ids: array<int, int>,
     *     excluded_ids: array<int, int>,
     * }
     */
    public function process(BulkDeletionRun $run): array
    {
        return match ($run->module) {
            BulkDeletionRun::MODULE_CUSTOMERS => $this->processCustomers($run),
            BulkDeletionRun::MODULE_SUBSCRIPTIONS => $this->processSubscriptions($run),
            default => throw new \InvalidArgumentException('Unsupported bulk deletion module: '.$run->module),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function processCustomers(BulkDeletionRun $run): array
    {
        $filters = $run->filters ?? [];
        $selectedIds = collect($run->selected_ids ?? [])->filter()->map(fn (mixed $id): int => (int) $id)->values();
        $excludedIds = collect($run->excluded_ids ?? [])->filter()->map(fn (mixed $id): int => (int) $id)->values();

        $query = Customer::withoutGlobalScopes()
            ->where('tenant_id', $run->tenant_id);

        if ($run->selection_mode === BulkDeletionRun::SELECTION_ALL) {
            $query->filter($filters);

            if ($excludedIds->isNotEmpty()) {
                $query->whereNotIn('id', $excludedIds->all());
            }
        } else {
            $query->whereIn('id', $selectedIds->all());
        }

        $targetCount = (clone $query)->count();
        $summary = $this->baseSummary($run, $targetCount);

        $this->processQuery(
            $query,
            function (Customer $customer) use (&$summary): void {
                $summary['processed_count']++;

                try {
                    $subscriptionSummary = $customer->subscriptions()
                        ->withoutGlobalScopes()
                        ->with(['invoices', 'router', 'customer'])
                        ->get()
                        ->map(function (Subscription $subscription): array {
                            return [
                                'id' => $subscription->id,
                                'subscription_code' => $subscription->subscription_code,
                                'status' => $subscription->status,
                                'billing_enabled' => (bool) $subscription->billing_enabled,
                            ];
                        })
                        ->all();

                    activity()->withoutLogging(function () use ($customer): void {
                        $customer->delete();
                    });

                    $summary['deleted_count']++;
                    $item = [
                        'id' => $customer->id,
                        'customer_code' => $customer->customer_code,
                        'name' => $customer->full_name,
                        'email' => $customer->email,
                        'subscription_count' => count($subscriptionSummary),
                        'subscriptions' => array_slice($subscriptionSummary, 0, 25),
                    ];

                    if (count($summary['deleted_items']) < 100) {
                        $summary['deleted_items'][] = $item;
                    } else {
                        $summary['deleted_items_truncated']++;
                    }
                } catch (Throwable $exception) {
                    $summary['failed_count']++;
                    $item = [
                        'id' => $customer->id,
                        'customer_code' => $customer->customer_code,
                        'name' => $customer->full_name,
                        'message' => $exception->getMessage(),
                    ];

                    if (count($summary['failed_items']) < 100) {
                        $summary['failed_items'][] = $item;
                    } else {
                        $summary['failed_items_truncated']++;
                    }
                }
            },
        );

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    private function processSubscriptions(BulkDeletionRun $run): array
    {
        $filters = $run->filters ?? [];
        $selectedIds = collect($run->selected_ids ?? [])->filter()->map(fn (mixed $id): int => (int) $id)->values();
        $excludedIds = collect($run->excluded_ids ?? [])->filter()->map(fn (mixed $id): int => (int) $id)->values();

        $query = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $run->tenant_id)
            ->with(['customer.organization', 'router']);

        if ($run->selection_mode === BulkDeletionRun::SELECTION_ALL) {
            $query->filter($filters);

            if ($excludedIds->isNotEmpty()) {
                $query->whereNotIn('id', $excludedIds->all());
            }
        } else {
            $query->whereIn('id', $selectedIds->all());
        }

        $targetCount = (clone $query)->count();
        $summary = $this->baseSummary($run, $targetCount);

        $this->processQuery(
            $query,
            function (Subscription $subscription) use (&$summary): void {
                $summary['processed_count']++;

                try {
                    $deletionSummary = $this->subscriptionDeletionService->delete($subscription, true);

                    $summary['deleted_count']++;
                    if (count($summary['deleted_items']) < 100) {
                        $summary['deleted_items'][] = $deletionSummary;
                    } else {
                        $summary['deleted_items_truncated']++;
                    }
                } catch (Throwable $exception) {
                    $summary['failed_count']++;
                    $item = [
                        'id' => $subscription->id,
                        'subscription_code' => $subscription->subscription_code,
                        'customer_name' => $subscription->customer?->full_name,
                        'message' => $exception->getMessage(),
                    ];

                    if (count($summary['failed_items']) < 100) {
                        $summary['failed_items'][] = $item;
                    } else {
                        $summary['failed_items_truncated']++;
                    }
                }
            },
        );

        return $summary;
    }

    /**
     * @param  Builder<Model>  $query
     * @param  callable(Model): void  $callback
     */
    private function processQuery(Builder $query, callable $callback): void
    {
        $query->orderBy('id')->chunkById(50, function ($items) use ($callback): void {
            foreach ($items as $item) {
                $callback($item);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function baseSummary(BulkDeletionRun $run, int $targetCount): array
    {
        return [
            'module' => $run->module,
            'action' => $run->action,
            'selection_mode' => $run->selection_mode,
            'filters' => $run->filters ?? [],
            'selected_ids' => $run->selected_ids ?? [],
            'excluded_ids' => $run->excluded_ids ?? [],
            'target_count' => $targetCount,
            'processed_count' => 0,
            'deleted_count' => 0,
            'failed_count' => 0,
            'deleted_items' => [],
            'deleted_items_truncated' => 0,
            'failed_items' => [],
            'failed_items_truncated' => 0,
        ];
    }

    /**
     * Create a compact activity log entry for the queued bulk delete run.
     *
     * @param  array<string, mixed>  $summary
     */
    public function logRun(BulkDeletionRun $run, string $event, array $summary, ?string $error = null): void
    {
        ActivityLog::create([
            'tenant_id' => $run->tenant_id,
            'user_id' => $run->user_id,
            'action' => 'bulk_delete.'.$event,
            'model_type' => BulkDeletionRun::class,
            'model_id' => $run->id,
            'old_values' => [
                'module' => $run->module,
                'action' => $run->action,
                'selection_mode' => $run->selection_mode,
            ],
            'new_values' => array_filter([
                'target_count' => $summary['target_count'] ?? null,
                'processed_count' => $summary['processed_count'] ?? null,
                'deleted_count' => $summary['deleted_count'] ?? null,
                'failed_count' => $summary['failed_count'] ?? null,
                'deleted_items_truncated' => $summary['deleted_items_truncated'] ?? null,
                'failed_items_truncated' => $summary['failed_items_truncated'] ?? null,
                'deleted_items' => array_slice($summary['deleted_items'] ?? [], 0, 10),
                'failed_items' => array_slice($summary['failed_items'] ?? [], 0, 10),
                'error' => $error,
            ], fn (mixed $value): bool => $value !== null && $value !== []),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
