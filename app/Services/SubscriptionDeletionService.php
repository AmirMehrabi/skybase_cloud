<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionDeletionService
{
    public function __construct(
        protected SubscriptionSessionDisconnectService $disconnectService,
    ) {}

    /**
     * Delete a subscription with its router, Radius, IP, and billing cleanup.
     *
     * @return array<string, mixed>
     */
    public function delete(Subscription $subscription, bool $suppressActivityLogs = false, bool $forceDelete = false): array
    {
        $operation = function () use ($subscription, $suppressActivityLogs, $forceDelete): array {
            $subscription->loadMissing([
                'customer.organization',
                'router',
                'ipRoutes',
                'items',
                'invoices.payments',
                'invoices.items',
            ]);

            $summary = [
                'subscription_id' => $subscription->id,
                'subscription_code' => $subscription->subscription_code,
                'customer_id' => $subscription->customer_id,
                'customer_name' => $subscription->customer?->full_name,
                'status_before' => $subscription->status,
                'status_after' => null,
                'suspended' => false,
                'ip_released' => false,
                'disconnect' => null,
                'billing' => [
                    'invoice_count' => 0,
                    'payment_count' => 0,
                    'invoice_item_count' => 0,
                    'subscription_item_count' => 0,
                ],
            ];

            return DB::transaction(function () use ($subscription, &$summary, $suppressActivityLogs, $forceDelete): array {
                if ($subscription->status !== 'suspended') {
                    $subscription->suspend();
                    $summary['suspended'] = true;
                }

                $freshSubscription = $subscription->fresh(['router', 'customer.organization', 'invoices.payments']);
                $invoiceCount = $freshSubscription->invoices->count();

                $disconnectResult = $this->disconnectService->disconnect($freshSubscription);
                $summary['disconnect'] = $disconnectResult->context();

                if (! $suppressActivityLogs) {
                    $this->disconnectService->recordActivity($freshSubscription, $disconnectResult);
                }

                if ($freshSubscription->usesSessionIpAllocation()) {
                    $allocator = app(SubscriptionIpAllocationService::class);
                    if ($disconnectResult->wasSuccessful()) {
                        $allocator->releaseAll($freshSubscription);
                    }
                    if ($forceDelete && $freshSubscription->ipLeases()->whereNull('released_at')->exists()) {
                        throw ValidationException::withMessages(['subscription' => 'Disconnect active sessions before permanently deleting this subscription.']);
                    }
                    $allocator->releaseReservations($freshSubscription);
                }

                if ($freshSubscription->isSystemManagedIp()) {
                    $summary['ip_released'] = $freshSubscription->releaseIpAddress();
                }

                $subscriptionItemCount = $freshSubscription->items()->count();
                $invoiceItemCount = 0;
                $paymentCount = 0;

                foreach ($freshSubscription->invoices as $invoice) {
                    $invoiceItemCount += $invoice->items()->count();
                    $paymentCount += $invoice->payments()->count();

                    $invoice->payments()->delete();
                    $invoice->items()->delete();
                    $invoice->delete();
                }

                $freshSubscription->items()->delete();
                if ($forceDelete) {
                    $freshSubscription->forceDelete();
                    $summary['status_after'] = 'force_deleted';
                } else {
                    $freshSubscription->delete();
                    $summary['status_after'] = 'deleted';
                }
                $summary['billing'] = [
                    'invoice_count' => $invoiceCount,
                    'payment_count' => $paymentCount,
                    'invoice_item_count' => $invoiceItemCount,
                    'subscription_item_count' => $subscriptionItemCount,
                ];

                return $summary;
            });
        };

        return $suppressActivityLogs
            ? activity()->withoutLogging($operation)
            : $operation();
    }
}
