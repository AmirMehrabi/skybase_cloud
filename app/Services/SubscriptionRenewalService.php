<?php

namespace App\Services;

use App\Http\Requests\Subscription\RenewSubscriptionRequest;
use App\Jobs\Subscriptions\SyncRenewedSubscriptionJob;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class SubscriptionRenewalService
{
    public function __construct(private BillingService $billing, private SubscriptionUsageService $usage) {}

    /**
     * @return array<string, mixed>
     */
    public function preview(Subscription $subscription): array
    {
        $subscription->load(['customer.organization', 'plan', 'items', 'invoices', 'restrictions', 'usageCycles']);
        $appliedAt = now()->startOfSecond();
        $periodEnd = $subscription->billingPeriodEndFor($appliedAt->copy()->startOfDay());
        $charge = $subscription->isBillable() ? round((float) $subscription->items->where('recurring', true)->sum('total'), 2) : 0;
        $outstanding = round((float) $subscription->invoices->whereIn('status', ['issued', 'partially_paid', 'overdue'])->sum('balance_due'), 2);
        $cycle = $subscription->usageCycles->whereNull('closed_at')->first();
        $blocker = $this->blocker($subscription, $appliedAt, $periodEnd);
        $warnings = [];

        if ($subscription->status === 'suspended') {
            $warnings[] = 'This subscription is suspended. Renewal will preserve the suspension; activate it separately when appropriate.';
        }

        foreach ($subscription->restrictions->whereNull('cleared_at')->where('type', '!=', 'quota') as $restriction) {
            $warnings[] = ucfirst($restriction->type).' restriction remains in effect: '.($restriction->reason ?: 'Service access remains restricted.');
        }

        return [
            'plan' => $subscription->plan?->name,
            'billing_cycle' => $subscription->billing_cycle,
            'current_expiry' => $subscription->end_date?->format('M d, Y H:i:s'),
            'new_expiry' => $periodEnd->copy()->endOfDay()->format('M d, Y H:i:s'),
            'next_billing_date' => $periodEnd->copy()->addDay()->format('M d, Y'),
            'billing_enabled' => $subscription->isBillable(),
            'charge' => $charge,
            'outstanding' => $outstanding,
            'total_outstanding' => round($outstanding + $charge, 2),
            'remaining_bytes' => $cycle?->effectiveAllowanceBytes() === null ? null : max(0, $cycle->effectiveAllowanceBytes() - $cycle->usedBytes()),
            'early' => $subscription->end_date?->isFuture() || $cycle?->ends_at->isFuture(),
            'warnings' => $warnings,
            'blocker' => $blocker,
            'token' => $blocker ? null : Crypt::encryptString(json_encode([
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
                'version' => $this->version($subscription),
                'date' => $appliedAt->toDateString(),
                'expires_at' => now()->addMinutes(10)->timestamp,
            ], JSON_THROW_ON_ERROR)),
        ];
    }

    public function renew(RenewSubscriptionRequest $request, Subscription $subscription): ?Invoice
    {
        try {
            $token = json_decode(Crypt::decryptString($request->validated('renewal_token')), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $exception) {
            $this->reject('The renewal preview is invalid. Close the dialog and try again.');
        }

        return $subscription->getConnection()->transaction(function () use ($request, $subscription, $token): ?Invoice {
            $lockedSubscription = Subscription::query()->where('tenant_id', $request->user()->tenant_id)->lockForUpdate()->findOrFail($subscription->id);
            $lockedSubscription->load(['customer.organization', 'plan', 'items', 'invoices', 'restrictions', 'usageCycles']);
            $appliedAt = now()->startOfSecond();
            $periodEnd = $lockedSubscription->billingPeriodEndFor($appliedAt->copy()->startOfDay());

            if (($token['subscription_id'] ?? null) !== $lockedSubscription->id
                || ($token['tenant_id'] ?? null) !== $lockedSubscription->tenant_id
                || ($token['expires_at'] ?? 0) < now()->timestamp
                || ($token['date'] ?? null) !== $appliedAt->toDateString()
                || ! hash_equals($this->version($lockedSubscription), $token['version'] ?? '')) {
                $this->reject('The subscription or billing details changed, or this preview expired. Close the dialog and review a fresh preview.');
            }

            if ($blocker = $this->blocker($lockedSubscription, $appliedAt, $periodEnd)) {
                $this->reject($blocker);
            }

            $oldExpiry = $lockedSubscription->end_date?->toDateTimeString();
            $invoice = $this->billing->createInvoiceForSubscription($lockedSubscription, $appliedAt);

            if ($lockedSubscription->isBillable() && ! $invoice) {
                $this->reject('No renewal invoice could be generated. The subscription was not renewed.');
            }

            $this->usage->restart($lockedSubscription, $appliedAt, $periodEnd->copy()->endOfDay(), $request->user()->id);
            $lockedSubscription->forceFill([
                'end_date' => $periodEnd->copy()->endOfDay(),
                'next_billing_date' => $periodEnd->copy()->addDay()->toDateString(),
                'status' => $lockedSubscription->status === 'suspended' ? 'suspended' : 'active',
                'activation_date' => $lockedSubscription->activation_date ?? $appliedAt,
                'activated_by' => $lockedSubscription->activated_by ?? $request->user()->id,
            ])->saveQuietly();

            activity()->useLog('subscription')->event('renewed')->performedOn($lockedSubscription)
                ->causedBy($request->user())->withProperties([
                    'applied_at' => $appliedAt->toDateTimeString(),
                    'previous_expiry' => $oldExpiry,
                    'new_expiry' => $lockedSubscription->end_date->toDateTimeString(),
                    'invoice_id' => $invoice?->id,
                    'charge' => $invoice?->total ?? 0,
                    'unused_allowance_forfeited' => true,
                ])->log('Subscription renewed');

            SyncRenewedSubscriptionJob::dispatch($lockedSubscription->id, (string) $lockedSubscription->tenant_id)
                ->onQueue('subscriptions')->afterCommit();

            return $invoice;
        });
    }

    private function blocker(Subscription $subscription, CarbonInterface $start, CarbonInterface $end): ?string
    {
        if ($subscription->status === 'cancelled') {
            return 'Cancelled subscriptions cannot be renewed.';
        }

        if (! $subscription->plan) {
            return 'Assign a plan before renewing this subscription.';
        }

        if ($subscription->isBillable() && $subscription->items->where('recurring', true)->isEmpty()) {
            return 'Add a recurring billable item before renewing this subscription.';
        }

        $overlap = Invoice::withoutGlobalScopes()->where('tenant_id', $subscription->tenant_id)
            ->where('subscription_id', $subscription->id)
            ->whereDate('billing_period_start', '<=', $end)
            ->whereDate('billing_period_end', '>=', $start)
            ->where(function ($query) use ($start, $end): void {
                $query->where('status', '!=', 'cancelled')
                    ->orWhere(function ($query) use ($start, $end): void {
                        $query->whereDate('billing_period_start', $start)->whereDate('billing_period_end', $end);
                    });
            })->first();

        return $overlap ? "Invoice {$overlap->invoice_number} already covers this period. Resolve the overlapping invoice before renewing." : null;
    }

    private function version(Subscription $subscription): string
    {
        return hash('sha256', json_encode([
            $subscription->getAttributes(),
            $subscription->customer?->getAttributes(),
            $subscription->customer?->organization?->getAttributes(),
            $subscription->plan?->getAttributes(),
            $subscription->items->sortBy('id')->map->getAttributes()->values()->all(),
            $subscription->invoices->sortBy('id')->map->getAttributes()->values()->all(),
            $subscription->restrictions->sortBy('id')->map->getAttributes()->values()->all(),
            $subscription->usageCycles->whereNull('closed_at')->pluck('id')->sort()->values()->all(),
        ], JSON_THROW_ON_ERROR));
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['renewal' => $message]);
    }
}
