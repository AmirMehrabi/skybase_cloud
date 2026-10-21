<?php

namespace Tests\Feature;

use App\Jobs\Subscriptions\SyncRenewedSubscriptionJob;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\RadiusAccountingRecord;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionRestriction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;
use App\Services\SubscriptionUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->travelTo(now()->setDate(2026, 10, 11)->setTime(14, 35, 12));
    }

    public function test_preview_is_read_only_and_show_page_has_renewal_dialog(): void
    {
        [$user, $subscription] = $this->subscription();
        $this->actingAs($user)->get(route('subscriptions.show', $subscription))->assertOk()
            ->assertSee('Renew service')->assertSee('Renew & generate invoice', false)
            ->assertSee('x-model="confirmed"', false);
        $this->getJson(route('subscriptions.renewal-preview', $subscription))->assertOk()
            ->assertJsonPath('charge', 99)->assertJsonPath('blocker', null)
            ->assertJsonPath('new_expiry', 'Nov 10, 2026 23:59:59');
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('subscription_usage_cycles', 0);
    }

    public function test_renewal_starts_at_application_time_and_bills_only_recurring_items(): void
    {
        [$user, $subscription] = $this->subscription();
        $start = $subscription->start_date->toDateTimeString();
        $token = $this->previewToken($user, $subscription);
        $this->travel(3)->minutes();
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertOk();

        $subscription->refresh();
        $this->assertSame($start, $subscription->start_date->toDateTimeString());
        $this->assertSame('2026-11-10 23:59:59', $subscription->end_date->toDateTimeString());
        $this->assertSame('2026-11-11', $subscription->next_billing_date->toDateString());
        $cycle = $subscription->usageCycles()->whereNull('closed_at')->firstOrFail();
        $this->assertSame('2026-10-11 14:38:12', $cycle->starts_at->toDateTimeString());
        $this->assertSame(10737418240, $cycle->allowance_bytes);
        $this->assertSame(0, $cycle->usedBytes());
        $invoice = $subscription->invoices()->firstOrFail();
        $this->assertSame('99.00', $invoice->total);
        $this->assertSame('99.00', $invoice->balance_due);
        $this->assertSame('2026-10-11', $invoice->billing_period_start->toDateString());
        $this->assertSame('2026-11-10', $invoice->billing_period_end->toDateString());
        $this->assertSame(1, $invoice->items()->count());
        $this->assertDatabaseHas('activity_log', ['subject_id' => $subscription->id, 'event' => 'renewed']);
        Queue::assertPushed(SyncRenewedSubscriptionJob::class);
        $this->get(route('subscriptions.show', $subscription))->assertOk()->assertSee('View renewal invoice');
    }

    public function test_renewal_closes_old_cycle_expires_adjustments_and_preserves_other_restrictions(): void
    {
        [$user, $subscription] = $this->subscription();
        $usage = app(SubscriptionUsageService::class);
        $oldCycle = $usage->currentCycle($subscription);
        $oldCycle->update(['used_download_bytes' => 10737418240, 'exempt_until' => now()->addWeek()]);
        $adjustment = $oldCycle->adjustments()->create([
            'tenant_id' => $subscription->tenant_id, 'subscription_id' => $subscription->id,
            'type' => 'bonus', 'bytes' => 100, 'reason' => 'Bonus', 'expires_at' => $oldCycle->ends_at,
        ]);
        foreach (['quota', 'billing', 'manual'] as $type) {
            SubscriptionRestriction::query()->create([
                'tenant_id' => $subscription->tenant_id, 'subscription_id' => $subscription->id,
                'type' => $type, 'effective_at' => now(),
            ]);
        }
        $token = $this->previewToken($user, $subscription);
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertOk();
        $this->assertNotNull($oldCycle->fresh()->closed_at);
        $this->assertSame(now()->subSecond()->toDateTimeString(), $oldCycle->fresh()->ends_at->toDateTimeString());
        $this->assertSame('expired', $adjustment->fresh()->status);
        $activeTypes = $subscription->restrictions()->whereNull('cleared_at')->pluck('type')->sort()->values()->all();
        $this->assertSame(['billing', 'manual'], $activeTypes);
        $newCycle = $subscription->usageCycles()->whereNull('closed_at')->firstOrFail();
        $this->assertNull($newCycle->exempt_until);
        $this->assertSame(0, $newCycle->adjustments()->count());
    }

    public function test_manual_suspension_is_preserved(): void
    {
        [$user, $subscription] = $this->subscription(['status' => 'suspended']);
        $token = $this->previewToken($user, $subscription);
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertOk();
        $this->assertSame('suspended', $subscription->fresh()->status);
    }

    public function test_non_overlapping_unpaid_invoice_is_preserved(): void
    {
        [$user, $subscription] = $this->subscription();
        $invoice = app(BillingService::class)->createInvoiceForSubscription($subscription, now()->subMonths(2));
        $token = $this->previewToken($user, $subscription);
        $this->getJson(route('subscriptions.renewal-preview', $subscription))->assertJsonPath('outstanding', 99)
            ->assertJsonPath('total_outstanding', 198);
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertOk();
        $this->assertSame('99.00', $invoice->fresh()->balance_due);
        $this->assertSame('0.00', $invoice->fresh()->paid_amount);
        $this->assertSame(2, $subscription->invoices()->count());
    }

    public function test_overlapping_invoice_blocks_renewal(): void
    {
        [$user, $subscription] = $this->subscription();
        $token = $this->previewToken($user, $subscription);
        app(BillingService::class)->createInvoiceForSubscription($subscription, now()->subDays(5));
        $this->getJson(route('subscriptions.renewal-preview', $subscription))->assertOk()
            ->assertJsonPath('token', null)->assertSee('already covers this period');
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))
            ->assertUnprocessable()->assertJsonValidationErrors('renewal');
        $this->assertDatabaseCount('subscription_usage_cycles', 0);
    }

    public function test_disabled_billing_renews_without_an_invoice_and_cannot_replay_token(): void
    {
        [$user, $subscription] = $this->subscription(['billing_enabled' => false]);
        $token = $this->previewToken($user, $subscription);
        $this->getJson(route('subscriptions.renewal-preview', $subscription))->assertJsonPath('charge', 0);
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertOk();
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertUnprocessable();
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('subscription_usage_cycles', 1);
        $this->assertFalse($subscription->fresh()->billing_enabled);
    }

    public function test_duplicate_submission_creates_only_one_invoice_and_cycle(): void
    {
        [$user, $subscription] = $this->subscription();
        $token = $this->previewToken($user, $subscription);
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertOk();
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertUnprocessable();
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('subscription_usage_cycles', 1);
    }

    public function test_confirmation_and_authentic_fresh_preview_are_required(): void
    {
        [$user, $subscription] = $this->subscription();
        $token = $this->previewToken($user, $subscription);
        $this->postJson(route('subscriptions.renew', $subscription), ['renewal_token' => $token])
            ->assertUnprocessable()->assertJsonValidationErrors('confirm_renewal');
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload('tampered'))
            ->assertUnprocessable()->assertJsonValidationErrors('renewal');
        $this->travel(11)->minutes();
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))
            ->assertUnprocessable()->assertJsonValidationErrors('renewal');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_price_change_invalidates_preview(): void
    {
        [$user, $subscription] = $this->subscription();
        $token = $this->previewToken($user, $subscription);
        $subscription->items()->where('recurring', true)->update(['total' => 200]);
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertUnprocessable();
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_cancelled_subscription_and_missing_recurring_items_cannot_renew(): void
    {
        [$user, $subscription] = $this->subscription(['status' => 'cancelled']);
        $this->actingAs($user)->getJson(route('subscriptions.renewal-preview', $subscription))
            ->assertJsonPath('token', null)->assertSee('Cancelled subscriptions cannot be renewed.');
        $subscription->forceFill(['status' => 'active'])->saveQuietly();
        $subscription->items()->delete();
        $this->getJson(route('subscriptions.renewal-preview', $subscription))
            ->assertJsonPath('token', null)->assertSee('Add a recurring billable item');
    }

    public function test_other_tenant_cannot_preview_or_renew(): void
    {
        [$user, $subscription] = $this->subscription();
        $token = $this->previewToken($user, $subscription);
        $otherTenant = Tenant::create(['id' => (string) Str::uuid(), 'name' => 'Other ISP', 'company_name' => 'Other ISP', 'email' => 'other@example.com', 'slug' => 'other-isp', 'status' => 'active']);
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id, 'role' => 'owner']);
        $this->actingAs($otherUser)->getJson(route('subscriptions.renewal-preview', $subscription))->assertNotFound();
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertNotFound();
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_renewal_baselines_exclude_old_session_bytes_even_after_reconciliation(): void
    {
        [$user, $subscription] = $this->subscription();
        $router = Router::factory()->create(['tenant_id' => $subscription->tenant_id]);
        $session = RadiusAccountingRecord::withoutEvents(fn (): RadiusAccountingRecord => RadiusAccountingRecord::withoutGlobalScopes()->create([
            'acctsessionid' => 'old-session', 'username' => $subscription->pppoe_username,
            'nasipaddress' => $router->ip_address, 'acctstarttime' => now()->subHour(),
            'acctinputoctets' => 1000, 'acctoutputoctets' => 2000,
        ]));
        $token = $this->previewToken($user, $subscription);
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertOk();
        $usage = app(SubscriptionUsageService::class);
        $this->assertSame(0, $usage->reconcile($subscription->fresh())->usedBytes());
        $session->forceFill(['acctinputoctets' => 1100, 'acctoutputoctets' => 2300])->save();
        $cycle = $usage->reconcile($subscription->fresh());
        $this->assertSame(100, $cycle->used_upload_bytes);
        $this->assertSame(300, $cycle->used_download_bytes);
        $session->forceFill(['acctstoptime' => now()->addMinute()])->save();
        $this->assertSame(400, $usage->reconcile($subscription->fresh())->usedBytes());
    }

    public function test_renewal_binds_tenant_router_addresses_and_excludes_other_tenant_sessions(): void
    {
        [$user, $subscription] = $this->subscription();
        [, $otherSubscription] = $this->subscription();
        $router = Router::factory()->create(['tenant_id' => $subscription->tenant_id, 'ip_address' => '192.0.2.1']);
        $otherRouter = Router::factory()->create(['tenant_id' => $otherSubscription->tenant_id, 'ip_address' => '192.0.2.2']);
        $sessions = [];

        foreach ([$router, $otherRouter] as $sessionRouter) {
            $sessions[] = RadiusAccountingRecord::withoutEvents(fn (): RadiusAccountingRecord => RadiusAccountingRecord::withoutGlobalScopes()->create([
                'acctsessionid' => 'session-'.$sessionRouter->id,
                'username' => $subscription->pppoe_username,
                'nasipaddress' => $sessionRouter->ip_address,
                'acctstarttime' => now()->subHour(),
                'acctinputoctets' => 1000,
                'acctoutputoctets' => 2000,
            ]));
        }

        $token = $this->previewToken($user, $subscription);
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertOk();

        $cycle = $subscription->usageCycles()->whereNull('closed_at')->firstOrFail();
        $this->assertSame([
            $sessions[0]->getKey() => ['upload' => 1000, 'download' => 2000],
        ], $cycle->accounting_baselines);

        foreach ($sessions as $session) {
            $session->forceFill(['acctinputoctets' => 1100, 'acctoutputoctets' => 2300])->save();
        }

        $this->assertSame(400, app(SubscriptionUsageService::class)->reconcile($subscription->fresh())->usedBytes());
    }

    public function test_renewal_rolls_back_when_the_usage_cycle_cannot_be_created(): void
    {
        [$user, $subscription] = $this->subscription();
        $oldExpiry = $subscription->end_date->toDateTimeString();
        $token = $this->previewToken($user, $subscription);
        $this->mock(SubscriptionUsageService::class)->shouldReceive('restart')->once()
            ->andThrow(new \RuntimeException('Usage cycle failed'));
        $this->withoutExceptionHandling();
        try {
            $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token));
            $this->fail('The renewal should have failed.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Usage cycle failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('subscription_usage_cycles', 0);
        $this->assertSame($oldExpiry, $subscription->fresh()->end_date->toDateTimeString());
        Queue::assertNotPushed(SyncRenewedSubscriptionJob::class);
    }

    public function test_users_without_subscription_action_permission_are_denied(): void
    {
        [$user, $subscription] = $this->subscription();
        $token = $this->previewToken($user, $subscription);
        $user->forceFill(['role' => 'billing'])->save();
        $this->actingAs($user)->getJson(route('subscriptions.renewal-preview', $subscription))->assertForbidden();
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertForbidden();
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_month_end_renewal_uses_calendar_billing_without_overflow(): void
    {
        $this->travelTo(now()->setDate(2027, 1, 31)->setTime(14, 35, 12));
        [$user, $subscription] = $this->subscription();
        $token = $this->previewToken($user, $subscription);
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertOk();
        $this->assertSame('2027-02-27 23:59:59', $subscription->fresh()->end_date->toDateTimeString());
        $this->assertSame('2027-02-28', $subscription->fresh()->next_billing_date->toDateString());
    }

    public function test_sync_job_preserves_billing_restriction_after_quota_is_cleared(): void
    {
        [$user, $subscription] = $this->subscription();
        foreach (['quota', 'billing'] as $type) {
            SubscriptionRestriction::query()->create([
                'tenant_id' => $subscription->tenant_id, 'subscription_id' => $subscription->id,
                'type' => $type, 'effective_at' => now(),
            ]);
        }
        $token = $this->previewToken($user, $subscription);
        $this->postJson(route('subscriptions.renew', $subscription), $this->payload($token))->assertOk();
        app()->call([new SyncRenewedSubscriptionJob($subscription->id, $subscription->tenant_id), 'handle']);
        $this->assertDatabaseHas('radusergroup', [
            'tenant_id' => $subscription->tenant_id, 'username' => $subscription->pppoe_username,
            'groupname' => 'skybase-restricted',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{User, Subscription}
     */
    private function subscription(array $attributes = []): array
    {
        $tenant = Tenant::create(['id' => (string) Str::uuid(), 'name' => 'Renewal ISP', 'company_name' => 'Renewal ISP', 'email' => 'renewal@example.com', 'slug' => 'renewal-'.Str::random(8), 'timezone' => 'UTC', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $this->actingAs($user);
        $customer = Customer::factory()->afterMaking(function (Customer $customer): void {
            foreach (['plan', 'site', 'router', 'ip_address', 'pppoe_username', 'pppoe_password', 'billing_cycle', 'activation_date'] as $attribute) {
                unset($customer->{$attribute});
            }
        })->create(['tenant_id' => $tenant->id, 'billing_enabled' => true, 'status' => 'active']);
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'billing_cycle' => 'monthly', 'data_limit' => 10, 'data_unit' => 'GB', 'unlimited' => false]);
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'plan_id' => $plan->id,
            'billing_cycle' => 'monthly', 'billing_enabled' => true, 'status' => 'active',
            'connection_type' => 'pppoe', 'pppoe_username' => 'renewal.'.Str::random(8), 'pppoe_password' => 'secret',
            'start_date' => now()->subMonths(3), 'end_date' => now()->subDay(), ...$attributes,
        ]);
        $item = $subscription->items()->create([
            'tenant_id' => $tenant->id, 'item_type' => 'plan', 'plan_id' => $plan->id,
            'description' => 'Monthly service', 'quantity' => 1, 'unit_price' => 100,
            'discount_type' => 'fixed', 'discount_amount' => 10, 'tax_percentage' => 10,
            'recurring' => true, 'billing_cycle' => 'monthly',
        ]);
        $item->calculateTotals();
        $subscription->items()->create([
            'tenant_id' => $tenant->id, 'item_type' => 'installation', 'description' => 'Installation',
            'quantity' => 1, 'unit_price' => 200, 'subtotal' => 200, 'total' => 200, 'recurring' => false,
        ]);

        return [$user, $subscription];
    }

    private function previewToken(User $user, Subscription $subscription): string
    {
        return $this->actingAs($user)->getJson(route('subscriptions.renewal-preview', $subscription))
            ->assertOk()->assertJsonPath('blocker', null)->json('token');
    }

    /**
     * @return array{renewal_token: string, confirm_renewal: bool}
     */
    private function payload(string $token): array
    {
        return ['renewal_token' => $token, 'confirm_renewal' => true];
    }
}
