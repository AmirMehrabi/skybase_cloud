<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Support\Notifications\NotificationEventRegistry;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function createInvoiceForSubscription(Subscription $subscription, ?CarbonInterface $periodStart = null, bool $includeOneTimeItems = false): ?Invoice
    {
        $subscription->loadMissing(['customer.organization', 'plan', 'items']);

        if (! $subscription->isBillable()) {
            return null;
        }

        return DB::transaction(function () use ($subscription, $periodStart, $includeOneTimeItems) {
            $lockedSubscription = Subscription::withoutGlobalScopes()
                ->with(['customer.organization', 'plan', 'items'])
                ->lockForUpdate()
                ->findOrFail($subscription->id);

            if (! $lockedSubscription->isBillable()) {
                return null;
            }

            $periodStart = $periodStart
                ? Carbon::parse($periodStart)->startOfDay()
                : Carbon::parse(
                    $lockedSubscription->next_billing_date
                        ?? $lockedSubscription->start_date
                        ?? $lockedSubscription->activation_date
                        ?? now(),
                )->startOfDay();

            $periodEnd = $lockedSubscription->billingPeriodEndFor($periodStart)->startOfDay();

            $existingInvoice = Invoice::withoutGlobalScopes()
                ->where('tenant_id', $lockedSubscription->tenant_id)
                ->where('subscription_id', $lockedSubscription->id)
                ->whereDate('billing_period_start', $periodStart)
                ->whereDate('billing_period_end', $periodEnd)
                ->first();

            if ($existingInvoice) {
                return $existingInvoice;
            }

            $items = $lockedSubscription->items
                ->filter(fn ($item) => $item->recurring || $includeOneTimeItems)
                ->values();

            if ($items->isEmpty()) {
                return null;
            }

            try {
                $invoice = Invoice::withoutGlobalScopes()->create([
                    'tenant_id' => $lockedSubscription->tenant_id,
                    'customer_id' => $lockedSubscription->customer_id,
                    'subscription_id' => $lockedSubscription->id,
                    'invoice_number' => $this->generateInvoiceNumber($lockedSubscription->tenant_id),
                    'billing_period_start' => $periodStart->toDateString(),
                    'billing_period_end' => $periodEnd->toDateString(),
                    'issue_date' => now()->toDateString(),
                    'due_date' => now()->addDays($lockedSubscription->effectiveGracePeriodDays())->toDateString(),
                    'status' => 'issued',
                ]);
            } catch (QueryException $exception) {
                $duplicate = Invoice::withoutGlobalScopes()
                    ->where('tenant_id', $lockedSubscription->tenant_id)
                    ->where('subscription_id', $lockedSubscription->id)
                    ->whereDate('billing_period_start', $periodStart)
                    ->whereDate('billing_period_end', $periodEnd)
                    ->first();

                if ($duplicate) {
                    return $duplicate;
                }

                throw $exception;
            }

            foreach ($items as $item) {
                $invoice->items()->create([
                    'subscription_item_id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'discount_type' => $item->discount_type,
                    'tax_percentage' => $item->tax_percentage,
                    'tax_amount' => $item->tax_amount,
                    'subtotal' => $item->subtotal,
                    'total' => $item->total,
                ]);
            }

            $invoice->recalculateTotals();
            $invoice = $invoice->fresh(['items', 'customer', 'subscription']);

            if ($invoice->customer) {
                app(TenantNotificationService::class)->notifyCustomer($invoice->customer, NotificationEventRegistry::INVOICE_CREATED, [
                    'title' => 'Your invoice is ready',
                    'body' => "Invoice {$invoice->invoice_number} is ready for review.",
                    'category' => 'billing',
                    'action_url' => route('customer.invoices.index'),
                ], $invoice);
            }

            $lockedSubscription->update([
                'next_billing_date' => $periodEnd->copy()->addDay()->toDateString(),
                'last_billed_at' => now(),
            ]);

            return $invoice;
        });
    }

    public function generateDueInvoices(?CarbonInterface $asOf = null): int
    {
        $asOf = $asOf ? Carbon::parse($asOf)->startOfDay() : today();
        $created = 0;

        Subscription::withoutGlobalScopes()
            ->with(['customer.organization', 'plan', 'items'])
            ->where('billing_enabled', true)
            ->whereIn('status', ['pending', 'active'])
            ->whereHas('customer', function ($query) {
                $query->withoutGlobalScopes()->where('billing_enabled', true);
            })
            ->where(function ($query) {
                $query->whereDoesntHave('customer.organization', function ($query) {
                    $query->withoutGlobalScopes()->where('billing_enabled', true);
                })->orWhereHas('customer.organization', function ($query) {
                    $query->withoutGlobalScopes()
                        ->where('billing_enabled', true)
                        ->whereColumn('organizations.default_plan_id', 'subscriptions.plan_id');
                });
            })
            ->where(function ($query) use ($asOf) {
                $query->whereNull('next_billing_date')
                    ->orWhereDate('next_billing_date', '<=', $asOf);
            })
            ->chunkById(100, function ($subscriptions) use (&$created) {
                foreach ($subscriptions as $subscription) {
                    if ($this->createInvoiceForSubscription($subscription)) {
                        $created++;
                    }
                }
            });

        return $created;
    }

    public function markOverdueInvoices(?CarbonInterface $asOf = null): int
    {
        $asOf = $asOf ? Carbon::parse($asOf)->startOfDay() : today();

        return Invoice::withoutGlobalScopes()
            ->outstanding()
            ->whereDate('due_date', '<', $asOf)
            ->update(['status' => 'overdue']);
    }

    public function suspendOverdueSubscriptions(?CarbonInterface $asOf = null): int
    {
        $asOf = $asOf ? Carbon::parse($asOf)->startOfDay() : today();
        $suspended = 0;

        Subscription::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('billing_enabled', true)
            ->where('auto_suspension_enabled', true)
            ->whereHas('customer', function ($query) {
                $query->withoutGlobalScopes()->where('billing_enabled', true);
            })
            ->where(function ($query) {
                $query->whereDoesntHave('customer.organization', function ($query) {
                    $query->withoutGlobalScopes()->where('billing_enabled', true);
                })->orWhereHas('customer.organization', function ($query) {
                    $query->withoutGlobalScopes()
                        ->where('billing_enabled', true)
                        ->whereColumn('organizations.default_plan_id', 'subscriptions.plan_id');
                });
            })
            ->whereHas('invoices', function ($query) use ($asOf) {
                $query->withoutGlobalScopes()
                    ->outstanding()
                    ->whereDate('due_date', '<', $asOf);
            })
            ->chunkById(100, function ($subscriptions) use (&$suspended) {
                foreach ($subscriptions as $subscription) {
                    $subscription->suspend('Automatic suspension for overdue invoices.');
                    $suspended++;
                }
            });

        return $suspended;
    }

    public function run(?CarbonInterface $asOf = null): array
    {
        return [
            'created_invoices' => $this->generateDueInvoices($asOf),
            'marked_overdue' => $this->markOverdueInvoices($asOf),
            'suspended_subscriptions' => $this->suspendOverdueSubscriptions($asOf),
        ];
    }

    protected function generateInvoiceNumber(string $tenantId): string
    {
        do {
            $number = 'INV-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (
            Invoice::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('invoice_number', $number)
                ->exists()
        );

        return $number;
    }
}
