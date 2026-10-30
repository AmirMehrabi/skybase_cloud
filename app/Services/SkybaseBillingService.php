<?php

namespace App\Services;

use App\Models\SkybaseAccount;
use App\Models\SkybaseInvoice;
use App\Models\SkybasePaymentAttempt;
use App\Models\Tenant;
use App\Models\TenantAdminEvent;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SkybaseBillingService
{
    public function __construct(private SkybasePackageCatalog $catalog, private DummySkybasePaymentGateway $gateway) {}

    public function account(string $tenantId): SkybaseAccount
    {
        abort_unless(app(TenantContext::class)->id() === $tenantId, 403);

        return SkybaseAccount::query()->where('tenant_id', $tenantId)->firstOrCreate(['tenant_id' => $tenantId]);
    }

    public function event(string $tenantId, string $event, string $reason, array $changes = []): void
    {
        TenantAdminEvent::query()->create(['tenant_id' => $tenantId, 'actor_id' => auth()->id(), 'event' => $event, 'reason' => $reason, 'changes' => $changes]);
    }

    public function checkout(string $tenantId, string $packageKey): ?SkybasePaymentAttempt
    {
        return (new Tenant)->getConnection()->transaction(function () use ($tenantId, $packageKey): ?SkybasePaymentAttempt {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            $account = $this->account($tenantId);
            $package = $this->catalog->get($packageKey);
            if (! $package['self_service']) {
                throw ValidationException::withMessages(['package_key' => 'Contact sales for Enterprise terms.']);
            }
            if ($account->paid_through?->isFuture() && $account->package_key !== $packageKey) {
                throw ValidationException::withMessages(['package_key' => 'Package changes take effect after the current paid period. Contact support for an immediate change.']);
            }
            if ($package['monthly_price_minor'] === 0) {
                $account->update($this->terms($packageKey) + ['status' => 'active', 'paid_through' => null, 'trial_ends_at' => null, 'cancel_at_period_end' => false]);
                $this->event($tenantId, 'billing.free_activated', 'Owner selected Free package');

                return null;
            }
            abort_unless(config('skybase_billing.dummy_gateway_enabled') && ! app()->environment('production'), 403, 'Simulated checkout is unavailable.');
            $invoice = SkybaseInvoice::query()->where('tenant_id', $tenantId)->where('package_key', $packageKey)->where('status', 'pending')->first();
            if (! $invoice) {
                $invoice = SkybaseInvoice::query()->create(['tenant_id' => $tenantId, 'package_key' => $packageKey, 'amount_minor' => $package['monthly_price_minor'], 'currency' => config('skybase_billing.currency'), 'subscriber_limit' => $package['subscriber_limit'], 'features' => $package['features']]);
            }
            $attempt = SkybasePaymentAttempt::query()->where('tenant_id', $tenantId)->where('skybase_invoice_id', $invoice->id)->where('status', 'pending')->where('expires_at', '>', now())->first();

            return $attempt ?? SkybasePaymentAttempt::query()->create(['tenant_id' => $tenantId, 'skybase_invoice_id' => $invoice->id, 'authority' => Str::random(48), 'expires_at' => now()->addMinutes(30)]);
        });
    }

    public function settle(string $tenantId, string $authority, string $outcome): SkybasePaymentAttempt
    {
        abort_unless(config('skybase_billing.dummy_gateway_enabled') && ! app()->environment('production'), 403);

        return (new Tenant)->getConnection()->transaction(function () use ($tenantId, $authority, $outcome): SkybasePaymentAttempt {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            $attempt = SkybasePaymentAttempt::query()->where('tenant_id', $tenantId)->where('authority', $authority)->lockForUpdate()->firstOrFail();
            $invoice = SkybaseInvoice::query()->where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($attempt->skybase_invoice_id);
            if ($attempt->status !== 'pending' || $invoice->status !== 'pending') {
                return $attempt;
            }
            if ($attempt->expires_at->isPast() || $outcome !== 'success') {
                $attempt->update(['status' => $attempt->expires_at->isPast() ? 'expired' : $outcome]);

                return $attempt;
            }
            $account = $this->account($tenantId);
            if ($account->paid_through?->isFuture() && $account->package_key !== $invoice->package_key) {
                throw ValidationException::withMessages(['package_key' => 'Account terms changed; start a new checkout.']);
            }
            $payment = $this->gateway->simulate((string) $invoice->id, $invoice->amount_minor, $invoice->currency);
            $start = $account->paid_through?->isFuture() ? $account->paid_through->copy() : now();
            $account->update(['package_key' => $invoice->package_key, 'status' => 'active', 'monthly_price_minor' => $invoice->amount_minor, 'currency' => $invoice->currency, 'subscriber_limit' => $invoice->subscriber_limit, 'features' => $invoice->features, 'paid_through' => $start->addMonthNoOverflow(), 'trial_ends_at' => null, 'cancel_at_period_end' => false]);
            $invoice->update(['status' => 'paid', 'paid_at' => now(), 'simulated' => true]);
            $attempt->update(['status' => 'succeeded', 'reference' => $payment['reference']]);
            $this->event($tenantId, 'billing.simulated_payment', 'Dummy payment; no money collected', ['invoice_id' => $invoice->id]);

            return $attempt;
        });
    }

    public function terms(string $key): array
    {
        $package = $this->catalog->get($key);

        return ['package_key' => $key, 'monthly_price_minor' => $package['monthly_price_minor'], 'currency' => config('skybase_billing.currency'), 'subscriber_limit' => $package['subscriber_limit'], 'features' => $package['features']];
    }
}
