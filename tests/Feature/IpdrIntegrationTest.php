<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\IpdrBinding;
use App\Models\IpdrOutbox;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\IpdrClient;
use App\Services\IpdrInventoryService;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class IpdrIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ipdr.enabled' => true, 'ipdr.url' => 'https://ipdr.example', 'ipdr.integration_token' => str_repeat('s', 48), 'ipdr.radius_timezone' => 'Asia/Tehran']);
        Http::preventStrayRequests();
        $this->travelTo(CarbonImmutable::parse('2026-10-18T10:00:00Z'));
    }

    private function subscriber(string $slug, string $ip = '192.0.2.1'): array
    {
        $tenant = Tenant::create(['id' => (string) Str::uuid(), 'name' => $slug, 'slug' => $slug, 'company_name' => $slug, 'email' => $slug.'@example.com', 'timezone' => 'UTC', 'status' => 'active']);

        return app(TenantContext::class)->run((string) $tenant->id, function () use ($tenant, $ip): array {
            $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
            $plan = Plan::factory()->create(['tenant_id' => $tenant->id]);
            $router = Router::factory()->create(['tenant_id' => $tenant->id, 'ip_address' => $ip, 'netflow_enabled' => true]);
            $subscription = Subscription::withoutEvents(fn () => Subscription::create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'plan_id' => $plan->id, 'subscription_code' => 'SUB-'.$tenant->slug, 'name' => 'Subscriber', 'router_id' => $router->id, 'connection_type' => 'pppoe', 'ip_management' => 'router', 'pppoe_username' => 'shared.username', 'base_price' => 0, 'total_price' => 0, 'billing_cycle' => 'monthly', 'status' => 'active', 'created_at' => now()->subDays(7)]));

            $subscription->forceFill(['created_at' => now()->subDays(7)])->saveQuietly();

            return [$tenant, $subscription, $router];
        });
    }

    public function test_static_reassignment_preserves_history_and_transaction_rollback_discards_outbox(): void
    {
        [$tenant, $subscription] = $this->subscriber('alpha');
        app(TenantContext::class)->run((string) $tenant->id, function () use ($tenant, $subscription): void {
            $subscription->update(['connection_type' => 'static', 'ip_address' => '10.0.0.2']);
            $old = IpdrBinding::query()->where('tenant_id', $tenant->id)->firstOrFail();
            $this->travel(10)->minutes();
            $subscription->update(['ip_address' => '10.0.0.3']);
            $this->assertNotNull($old->fresh()->valid_until);
            $this->assertDatabaseCount('ipdr_bindings', 2);
            $this->assertDatabaseCount('ipdr_outboxes', 3);
            DB::beginTransaction();
            $subscription->update(['ip_address' => '10.0.0.4']);
            DB::rollBack();
            $this->assertDatabaseCount('ipdr_bindings', 2);
            $this->assertDatabaseCount('ipdr_outboxes', 3);
            $subscription->refresh()->delete();
            $this->assertSame(0, IpdrBinding::query()->where('tenant_id', $tenant->id)->whereNull('valid_until')->count());
        });
    }

    public function test_radius_uses_nas_ownership_and_converts_local_timestamps_to_utc(): void
    {
        [$alpha, $subscription, $router] = $this->subscriber('alpha');
        [$beta] = $this->subscriber('beta', '192.0.2.2');
        DB::table('radacct')->insert(['acctsessionid' => 's1', 'acctuniqueid' => 'u1', 'username' => 'shared.username', 'nasipaddress' => $router->ip_address, 'framedipaddress' => '10.0.0.2', 'acctstarttime' => '2026-10-18 12:00:00', 'acctupdatetime' => '2026-10-18 13:00:00']);
        app(TenantContext::class)->run((string) $alpha->id, fn () => app(IpdrInventoryService::class)->radius((string) $alpha->id));
        app(TenantContext::class)->run((string) $beta->id, fn () => app(IpdrInventoryService::class)->radius((string) $beta->id));
        $this->assertDatabaseCount('ipdr_bindings', 1);
        $this->assertDatabaseHas('ipdr_bindings', ['tenant_id' => $alpha->id, 'subscription_id' => $subscription->id, 'valid_from' => '2026-10-18 08:30:00', 'valid_until' => '2026-10-18 09:45:00']);
        DB::table('radacct')->update(['acctstoptime' => '2026-10-18 13:10:00']);
        app(TenantContext::class)->run((string) $alpha->id, fn () => app(IpdrInventoryService::class)->radius((string) $alpha->id));
        $this->assertDatabaseHas('ipdr_bindings', ['revision' => 2, 'valid_until' => '2026-10-18 09:40:00']);
        $this->assertDatabaseCount('ipdr_outboxes', 2);
    }

    public function test_delivery_is_retryable_and_acknowledgement_is_required(): void
    {
        [$tenant, $subscription] = $this->subscriber('alpha');
        app(TenantContext::class)->run((string) $tenant->id, function () use ($tenant, $subscription): void {
            $subscription->update(['connection_type' => 'static', 'ip_address' => '10.0.0.2']);
            $acknowledged = false;
            Http::fake(function () use (&$acknowledged) {
                return Http::response(['accepted' => $acknowledged]);
            });
            try {
                app(IpdrInventoryService::class)->deliver((string) $tenant->id, app(IpdrClient::class));
                $this->fail('An acknowledgement is required.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('acknowledge', $exception->getMessage());
            }
            $this->assertSame(1, IpdrOutbox::query()->where('tenant_id', $tenant->id)->whereNull('delivered_at')->count());
            $acknowledged = true;
            $this->assertSame(1, app(IpdrInventoryService::class)->deliver((string) $tenant->id, app(IpdrClient::class)));
            $this->assertSame(0, app(IpdrInventoryService::class)->deliver((string) $tenant->id, app(IpdrClient::class)));
            Http::assertSent(fn ($request) => $request->hasHeader('X-Skybase-Tenant', (string) $tenant->id));
        });
    }

    public function test_subscription_report_is_authorized_and_never_accepts_a_tenant_override(): void
    {
        [$tenant, $subscription] = $this->subscriber('alpha');
        [, $other] = $this->subscriber('beta', '192.0.2.2');
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        Http::fake(['ipdr.example/*' => Http::response(['usage' => ['download_bytes' => 10], 'latest_flows' => []])]);
        $this->actingAs($user)->getJson(route('subscriptions.ipdr', $subscription).'?tenant_id=another')->assertOk()->assertJsonPath('usage.download_bytes', 10);
        Http::assertSent(fn ($request) => $request->hasHeader('X-Skybase-Tenant', (string) $tenant->id) && $request['subscription_id'] === (string) $subscription->id && ! isset($request['tenant_id']));
        $this->getJson(route('subscriptions.ipdr', $other))->assertNotFound();
    }

    public function test_local_collector_is_disabled_when_using_ipdr(): void
    {
        $this->artisan('netflow:collect')->assertFailed();
    }
}
