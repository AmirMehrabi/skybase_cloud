<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\RadiusAccountingRecord;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionUsageCycle;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionRestrictionService;
use App\Services\SubscriptionUsageService;
use App\Services\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionUsageReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(SubscriptionRestrictionService::class, function ($mock): void {
            $mock->shouldReceive('clear')->andReturn(false);
            $mock->shouldReceive('restrict')->andReturn(false);
        });
    }

    public function test_scheduled_reconciliation_processes_multiple_tenants_without_a_logged_in_user(): void
    {
        $subscriptions = [$this->subscription(), $this->subscription()];
        foreach ($subscriptions as $subscription) {
            $this->accountingSession($subscription, ['acctinputoctets' => 100, 'acctoutputoctets' => 200]);
        }

        $this->assertNull(app(TenantContext::class)->id());
        $this->artisan('usage:reconcile')->expectsOutput('Reconciled 2 subscription(s).')->assertSuccessful();

        foreach ($subscriptions as $subscription) {
            $cycle = SubscriptionUsageCycle::withoutGlobalScopes()->where('tenant_id', $subscription->tenant_id)
                ->where('subscription_id', $subscription->id)->firstOrFail();
            $this->assertSame(300, $cycle->usedBytes());
        }
        $this->assertNull(app(TenantContext::class)->id());
    }

    public function test_renewal_baselines_and_reconciliation_include_an_unmapped_nas_for_a_unique_username(): void
    {
        $subscription = $this->subscription();
        Router::factory()->create(['tenant_id' => $subscription->tenant_id, 'ip_address' => '10.8.0.3']);
        $session = $this->accountingSession($subscription, ['nasipaddress' => '10.8.0.2', 'acctinputoctets' => 100, 'acctoutputoctets' => 200]);
        $usage = app(SubscriptionUsageService::class);
        $cycle = $usage->restart($subscription, now(), now()->addMonth(), User::factory()->create(['tenant_id' => $subscription->tenant_id])->id);
        $this->assertSame([$session->getKey() => ['upload' => 100, 'download' => 200]], $cycle->accounting_baselines);
        $this->assertSame(0, $usage->reconcile($subscription)->usedBytes());
        $session->forceFill(['acctinputoctets' => 150, 'acctoutputoctets' => 400])->save();
        $this->accountingSession($subscription, ['nasipaddress' => '10.8.0.2', 'acctstarttime' => now()->addMinute(), 'acctinputoctets' => 50, 'acctoutputoctets' => 100]);
        $this->assertSame(400, $usage->reconcile($subscription)->usedBytes());
    }

    public function test_a_missing_baseline_for_a_session_spanning_renewal_does_not_charge_old_traffic(): void
    {
        $subscription = $this->subscription();
        $usage = app(SubscriptionUsageService::class);
        $cycle = $usage->currentCycle($subscription);
        $cycle->update(['starts_at' => now(), 'accounting_baselines' => []]);
        $session = $this->accountingSession($subscription, ['acctoutputoctets' => 1000]);
        $this->assertSame(0, $usage->reconcile($subscription)->usedBytes());
        $this->assertSame([$session->getKey() => ['upload' => 0, 'download' => 1000]], $cycle->fresh()->accounting_baselines);
        $session->forceFill(['acctoutputoctets' => 1200])->save();
        $this->assertSame(200, $usage->reconcile($subscription)->usedBytes());
    }

    public function test_accounting_excludes_foreign_tenant_records_and_ambiguous_legacy_sessions(): void
    {
        Schema::table('radacct', fn (Blueprint $table) => $table->string('tenant_id')->nullable());
        $subscription = $this->subscription();
        $other = $this->subscription(['pppoe_username' => $subscription->pppoe_username]);
        Router::factory()->create(['tenant_id' => $subscription->tenant_id, 'ip_address' => '10.8.0.3']);
        Router::factory()->create(['tenant_id' => $other->tenant_id, 'ip_address' => '10.8.0.4']);
        $this->accountingSession($subscription, ['tenant_id' => $subscription->tenant_id, 'nasipaddress' => '10.8.0.2', 'acctoutputoctets' => 100]);
        $this->accountingSession($subscription, ['nasipaddress' => '10.8.0.3', 'acctoutputoctets' => 200]);
        $this->accountingSession($subscription, ['tenant_id' => $other->tenant_id, 'nasipaddress' => '10.8.0.3', 'acctoutputoctets' => 400]);
        $this->accountingSession($subscription, ['nasipaddress' => '10.8.0.2', 'acctoutputoctets' => 800]);
        $this->accountingSession($subscription, ['nasipaddress' => '10.8.0.4', 'acctoutputoctets' => 1600]);

        $this->assertSame(300, app(SubscriptionUsageService::class)->reconcile($subscription)->usedBytes());
    }

    public function test_legacy_sessions_from_another_tenants_router_are_excluded_even_for_a_unique_username(): void
    {
        $subscription = $this->subscription();
        $other = $this->subscription();
        Router::factory()->create(['tenant_id' => $other->tenant_id, 'ip_address' => '10.8.0.4']);
        $this->accountingSession($subscription, ['nasipaddress' => '10.8.0.2', 'acctoutputoctets' => 100]);
        $this->accountingSession($subscription, ['nasipaddress' => '10.8.0.4', 'acctoutputoctets' => 200]);
        $this->assertSame(100, app(SubscriptionUsageService::class)->reconcile($subscription)->usedBytes());
    }

    public function test_rollover_runs_with_each_tenants_context(): void
    {
        $subscriptions = [$this->subscription(), $this->subscription()];
        foreach ($subscriptions as $subscription) {
            app(SubscriptionUsageService::class)->currentCycle($subscription)->update(['ends_at' => now()->subMinute()]);
        }
        $this->artisan('usage:rollover')->expectsOutput('Rolled over 2 usage cycle(s).')->assertSuccessful();
        foreach ($subscriptions as $subscription) {
            $cycles = SubscriptionUsageCycle::withoutGlobalScopes()->where('tenant_id', $subscription->tenant_id)
                ->where('subscription_id', $subscription->id)->get();
            $this->assertCount(2, $cycles);
            $this->assertSame(1, $cycles->whereNull('closed_at')->count());
            $this->assertSame(107374182400, $cycles->whereNull('closed_at')->first()->allowance_bytes);
        }
    }

    private function subscription(array $attributes = []): Subscription
    {
        $tenant = Tenant::create(['id' => (string) Str::uuid(), 'name' => 'Usage ISP', 'company_name' => 'Usage ISP', 'slug' => 'usage-'.Str::random(8), 'email' => 'usage@example.com', 'status' => 'active']);
        $customer = Customer::withoutEvents(fn () => Customer::factory()->make(['tenant_id' => $tenant->id]));
        foreach (['plan', 'site', 'router', 'ip_address', 'pppoe_username', 'pppoe_password', 'billing_cycle', 'activation_date'] as $attribute) {
            unset($customer->{$attribute});
        }
        $customer->saveQuietly();
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'data_limit' => 100, 'data_unit' => 'GB', 'unlimited' => false]);

        return Subscription::withoutEvents(fn () => Subscription::create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'plan_id' => $plan->id,
            'subscription_code' => 'SUB-'.Str::random(8), 'connection_type' => 'pppoe',
            'pppoe_username' => 'usage.'.Str::random(8), 'pppoe_password' => 'secret',
            'billing_cycle' => 'monthly', 'status' => 'active', 'start_date' => now()->startOfMonth(), ...$attributes,
        ]));
    }

    private function accountingSession(Subscription $subscription, array $attributes = []): RadiusAccountingRecord
    {
        return RadiusAccountingRecord::withoutEvents(fn () => RadiusAccountingRecord::withoutGlobalScopes()->forceCreate([
            'acctsessionid' => Str::random(16), 'acctuniqueid' => Str::random(16),
            'username' => $subscription->pppoe_username,
            'nasipaddress' => '10.8.0.2', 'acctstarttime' => now()->subHour(),
            'acctinputoctets' => 0, 'acctoutputoctets' => 0, ...$attributes,
        ]));
    }
}
