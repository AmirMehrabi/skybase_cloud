<?php

namespace Tests\Feature;

use App\Models\SkybaseAccount;
use App\Models\SkybasePaymentAttempt;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SkybaseBillingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkybaseBillingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['skybase_billing.enabled' => true, 'skybase_billing.dummy_gateway_enabled' => true]);
        $this->tenant = Tenant::factory()->create();
        $this->actingAs(User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'owner']));
    }

    public function test_server_priced_checkout_and_repeated_settlement(): void
    {
        $this->post(route('skybase.billing.checkout'), ['package_key' => 'starter', 'amount_minor' => 1])->assertRedirect();
        $attempt = SkybasePaymentAttempt::query()->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertDatabaseHas('skybase_invoices', ['amount_minor' => 6900, 'status' => 'pending']);
        $this->get(route('skybase.billing.payment', $attempt->authority))->assertOk()->assertSee('Dummy payment');
        $this->post(route('skybase.billing.settle', $attempt->authority), ['outcome' => 'success'])->assertRedirect();
        $paidThrough = SkybaseAccount::query()->where('tenant_id', $this->tenant->id)->firstOrFail()->paid_through->toDateTimeString();
        $this->post(route('skybase.billing.settle', $attempt->authority), ['outcome' => 'success'])->assertRedirect();
        $this->assertSame($paidThrough, SkybaseAccount::query()->where('tenant_id', $this->tenant->id)->firstOrFail()->paid_through->toDateTimeString());
        $this->assertDatabaseHas('skybase_invoices', ['status' => 'paid', 'simulated' => true]);
        $this->get(route('skybase.billing.index'))->assertOk()->assertSee('simulated');
    }

    public function test_cancel_failure_expiry_and_retry_never_mark_invoice_paid(): void
    {
        $billing = app(SkybaseBillingService::class);
        app(TenantContext::class)->run($this->tenant->id, function () use ($billing): void {
            foreach (['cancelled', 'failed', 'expired'] as $outcome) {
                $attempt = $billing->checkout($this->tenant->id, 'starter');
                if ($outcome === 'expired') {
                    $attempt->update(['expires_at' => now()->subSecond()]);
                }
                $billing->settle($this->tenant->id, $attempt->authority, $outcome === 'expired' ? 'success' : $outcome);
                $this->assertSame($outcome, $attempt->fresh()->status);
            }
        });
        $this->assertDatabaseMissing('skybase_invoices', ['status' => 'paid']);
    }

    public function test_cross_tenant_authority_and_staff_access_are_rejected(): void
    {
        $attempt = app(TenantContext::class)->run($this->tenant->id, fn () => app(SkybaseBillingService::class)->checkout($this->tenant->id, 'starter'));
        $other = Tenant::factory()->create();
        $this->actingAs(User::factory()->create(['tenant_id' => $other->id, 'role' => 'owner']));
        $this->get(route('skybase.billing.payment', $attempt->authority))->assertNotFound();
        $this->post(route('skybase.billing.settle', $attempt->authority), ['outcome' => 'success'])->assertNotFound();
        $this->actingAs(User::factory()->create(['tenant_id' => $other->id, 'role' => 'support']));
        $this->get(route('skybase.billing.index'))->assertForbidden();
    }

    public function test_free_enterprise_cancellation_and_deployment_switch(): void
    {
        $this->post(route('skybase.billing.checkout'), ['package_key' => 'free'])->assertRedirect();
        $this->assertDatabaseHas('skybase_accounts', ['tenant_id' => $this->tenant->id, 'package_key' => 'free', 'subscriber_limit' => 40]);
        $this->post(route('skybase.billing.checkout'), ['package_key' => 'enterprise'])->assertSessionHasErrors('package_key');
        $this->post(route('skybase.billing.cancel'))->assertRedirect();
        $this->assertDatabaseHas('skybase_accounts', ['cancel_at_period_end' => true]);
        config(['skybase_billing.enabled' => false]);
        $this->get(route('skybase.billing.index'))->assertNotFound();
    }

    public function test_renewal_adds_one_month_and_package_switch_is_rejected_during_paid_term(): void
    {
        $context = app(TenantContext::class);
        $context->run($this->tenant->id, function (): void {
            $billing = app(SkybaseBillingService::class);
            $first = $billing->checkout($this->tenant->id, 'starter');
            $billing->settle($this->tenant->id, $first->authority, 'success');
            $through = $billing->account($this->tenant->id)->paid_through->copy();
            $renewal = $billing->checkout($this->tenant->id, 'starter');
            $billing->settle($this->tenant->id, $renewal->authority, 'success');
            $this->assertTrue($billing->account($this->tenant->id)->paid_through->equalTo($through->addMonthNoOverflow()));
        });
        $this->post(route('skybase.billing.checkout'), ['package_key' => 'growth'])->assertSessionHasErrors('package_key');
        $this->post(route('skybase.billing.checkout'), ['package_key' => 'forged'])->assertSessionHasErrors('package_key');
        $this->assertDatabaseCount('skybase_invoices', 2);
    }

    public function test_production_and_disabled_dummy_checkout_cannot_create_paid_invoices(): void
    {
        config(['skybase_billing.dummy_gateway_enabled' => false]);
        $this->post(route('skybase.billing.checkout'), ['package_key' => 'starter'])->assertForbidden();
        config(['skybase_billing.dummy_gateway_enabled' => true]);
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->app->instance('env', 'production');
        $this->post(route('skybase.billing.checkout'), ['package_key' => 'starter'])->assertForbidden();
        $this->assertDatabaseCount('skybase_invoices', 0);
    }
}
