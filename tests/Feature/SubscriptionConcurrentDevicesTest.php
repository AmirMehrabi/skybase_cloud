<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RouterOs\RouterOsCoaClient;
use App\Services\SubscriptionIpAllocationService;
use App\Services\SubscriptionSessionDisconnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionConcurrentDevicesTest extends TestCase
{
    use RefreshDatabase;

    private Subscription $subscription;

    private SubscriptionIpAllocationService $allocator;

    private IpPool $pool;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);
        $this->actingAs($user);
        $customer = Customer::factory()->active()->create(['tenant_id' => $tenant->id, 'billing_enabled' => false]);
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);
        $router = Router::factory()->online()->create(['tenant_id' => $tenant->id]);
        $this->pool = IpPool::factory()->create(['tenant_id' => $tenant->id, 'router_id' => $router->id, 'status' => 'active', 'name' => 'Guest Pool', 'network_address' => '10.0.0.0', 'cidr' => 24]);
        foreach (range(21, 24) as $suffix) {
            IpAddress::factory()->available()->create(['tenant_id' => $tenant->id, 'ip_pool_id' => $this->pool->id, 'ip_address' => '10.0.0.'.$suffix]);
        }
        $this->token = str_repeat('s', 40);
        config(['radius_allocation.tokens' => [$tenant->id => $this->token]]);
        $this->subscription = Subscription::create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'plan_id' => $plan->id,
            'router_id' => $router->id, 'subscription_code' => 'SUB-CONCURRENT', 'name' => 'Guest access',
            'service_type' => 'hotspot', 'connection_type' => 'pppoe', 'pppoe_username' => 'guest',
            'pppoe_password' => 'password', 'status' => 'active', 'billing_cycle' => 'monthly',
            'billing_enabled' => false, 'ip_management' => 'system', 'ip_pool_id' => $this->pool->id,
            'concurrent_devices' => 2, 'ip_allocation' => 'dynamic_pool',
        ]);
        $this->allocator = app(SubscriptionIpAllocationService::class);
    }

    public function test_create_and_edit_show_the_shared_network_controls(): void
    {
        $this->get(route('subscriptions.create'))->assertOk()->assertSee('Concurrent Devices')->assertSee('Reserved subscription IPs')->assertSee('Fill remaining automatically');
        $this->get(route('subscriptions.edit', $this->subscription))->assertOk()->assertSee('Concurrent Devices')->assertSee('Automatic from pool');
        $this->get(route('subscriptions.show', $this->subscription))->assertOk()->assertSee('0 of 2 devices connected');
    }

    public function test_two_sessions_receive_different_addresses_and_retries_reuse_the_same_lease(): void
    {
        $first = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-1');
        $retry = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-1');
        $second = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-2');
        $this->assertSame($first->id, $retry->id);
        $this->assertNotSame($first->ip_address, $second->ip_address);
        $this->assertNull($this->subscription->fresh()->ip_address);
        $this->assertDatabaseMissing('radreply', ['tenant_id' => $this->subscription->tenant_id, 'username' => 'guest', 'attribute' => 'Framed-IP-Address']);
        $this->assertDatabaseHas('radreply', ['tenant_id' => $this->subscription->tenant_id, 'username' => 'guest', 'attribute' => 'Port-Limit', 'value' => '2']);
        $this->expectException(ValidationException::class);
        $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-3');
    }

    public function test_released_dynamic_address_is_available_and_a_late_stop_cannot_release_a_new_lease(): void
    {
        $first = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-1');
        $this->allocator->release($this->subscription, $first->token);
        $second = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-2');
        $this->allocator->release($this->subscription, $first->token);
        $this->assertNull($second->fresh()->released_at);
        $this->assertDatabaseHas('ip_addresses', ['tenant_id' => $this->subscription->tenant_id, 'ip_address' => $second->ip_address, 'status' => 'assigned']);
    }

    public function test_reserved_addresses_stay_owned_after_disconnect_and_do_not_spill_into_the_pool(): void
    {
        $addresses = ['10.0.0.21', '10.0.0.22'];
        $this->allocator->configure($this->subscription, ['ip_allocation' => 'reserved_set', 'reserved_ip_addresses' => $addresses]);
        $this->subscription->update(['ip_allocation' => 'reserved_set']);
        $lease = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-1');
        $this->assertContains($lease->ip_address, $addresses);
        $this->allocator->release($this->subscription, $lease->token);
        $this->assertDatabaseHas('ip_addresses', ['tenant_id' => $this->subscription->tenant_id, 'ip_address' => $lease->ip_address, 'status' => 'assigned', 'subscription_code' => $this->subscription->subscription_code]);
    }

    public function test_allocation_settings_cannot_change_while_a_lease_is_active(): void
    {
        $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-1');
        $this->putJson(route('subscriptions.update', $this->subscription), ['concurrent_devices' => 3])->assertUnprocessable()->assertJsonValidationErrors('concurrent_devices');
        $this->assertSame(2, $this->subscription->fresh()->concurrent_devices);
    }

    public function test_validation_rejects_incomplete_duplicate_and_foreign_reservations(): void
    {
        $url = route('subscriptions.update', $this->subscription);
        $this->putJson($url, ['ip_allocation' => 'reserved_set', 'reserved_ip_addresses' => ['10.0.0.21']])->assertUnprocessable()->assertJsonValidationErrors('reserved_ip_addresses');
        $this->putJson($url, ['ip_allocation' => 'reserved_set', 'reserved_ip_addresses' => ['10.0.0.21', '10.0.0.21']])->assertUnprocessable()->assertJsonValidationErrors('reserved_ip_addresses.0');
        $this->putJson($url, ['ip_allocation' => 'reserved_set', 'reserved_ip_addresses' => ['10.0.0.21', '192.0.2.2']])->assertUnprocessable()->assertJsonValidationErrors('reserved_ip_addresses.1');
    }

    public function test_valid_edit_persists_the_reserved_set(): void
    {
        $this->put(route('subscriptions.update', $this->subscription), ['ip_allocation' => 'reserved_set', 'reserved_ip_addresses' => ['10.0.0.21', '10.0.0.22']])->assertRedirect();
        $this->assertSame(['10.0.0.21', '10.0.0.22'], $this->subscription->fresh()->reserved_ip_addresses);
        $this->assertDatabaseHas('ip_addresses', ['tenant_id' => $this->subscription->tenant_id, 'ip_address' => '10.0.0.21', 'subscription_code' => 'SUB-CONCURRENT']);
    }

    public function test_pending_allocations_expire_but_confirmed_sessions_are_not_blindly_reclaimed(): void
    {
        $first = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-1');
        $this->allocator->confirm($this->subscription, $first->token);
        $pending = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-2');
        $this->travel(3)->minutes();
        $replacement = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-3');
        $this->assertNotNull($pending->fresh()->released_at);
        $this->assertNull($first->fresh()->released_at);
        $this->assertNotSame($first->ip_address, $replacement->ip_address);
    }

    public function test_pool_exhaustion_preserves_existing_sessions(): void
    {
        $this->pool->ipAddresses()->where('tenant_id', $this->subscription->tenant_id)->where('ip_address', '!=', '10.0.0.21')->update(['status' => 'blocked']);
        $lease = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-1');
        try {
            $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-2');
            $this->fail('An exhausted pool must reject a new login.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('ip_pool_id', $exception->errors());
        }
        $this->assertNull($lease->fresh()->released_at);
    }

    public function test_radius_hooks_require_a_tenant_token_and_scope_the_router_and_subscription(): void
    {
        $url = '/api/v1/radius/'.$this->subscription->tenant_id.'/allocate';
        $payload = ['username' => 'guest', 'router_id' => $this->subscription->router_id, 'session_key' => 'login-1'];
        $this->postJson($url, $payload)->assertForbidden();
        $response = $this->withToken($this->token)->postJson($url, $payload)->assertOk();
        $this->assertSame('10.0.0.21', $response->json('reply:Framed-IP-Address.value'));
        $this->withToken($this->token)->postJson($url, [...$payload, 'router_id' => $this->subscription->router_id + 999])->assertNotFound();
        $otherTenant = Tenant::factory()->create();
        $this->withToken($this->token)->postJson('/api/v1/radius/'.$otherTenant->id.'/allocate', $payload)->assertForbidden();
    }

    public function test_legacy_subscription_edits_keep_router_limits_and_fixed_ip(): void
    {
        $this->subscription->update(['concurrent_devices' => null, 'ip_allocation' => null, 'ip_address' => '10.0.0.21']);
        $this->put(route('subscriptions.update', $this->subscription), ['name' => 'Renamed', 'concurrent_devices' => 1, 'ip_allocation' => 'fixed'])->assertRedirect();
        $this->assertNull($this->subscription->fresh()->concurrent_devices);
        $this->assertSame('10.0.0.21', $this->subscription->fresh()->ip_address);
        $this->assertDatabaseMissing('radreply', ['tenant_id' => $this->subscription->tenant_id, 'username' => 'guest', 'attribute' => 'Port-Limit']);
    }

    public function test_create_stores_a_reserved_set_without_a_primary_ip(): void
    {
        $response = $this->postJson(route('subscriptions.store'), $this->creationPayload([
            'ip_allocation' => 'reserved_set',
            'reserved_ip_addresses' => ['10.0.0.21', '10.0.0.22'],
        ]))->assertSuccessful();
        $created = Subscription::query()->where('tenant_id', $this->subscription->tenant_id)->where('pppoe_username', 'new-guest')->firstOrFail();
        $this->assertNull($created->ip_address);
        $this->assertSame(['10.0.0.21', '10.0.0.22'], $created->reserved_ip_addresses);
        $this->assertDatabaseHas('ip_addresses', ['tenant_id' => $created->tenant_id, 'ip_address' => '10.0.0.21', 'subscription_code' => $created->subscription_code]);
    }

    public function test_create_defaults_to_one_device_and_preserves_the_single_ip_flow(): void
    {
        $payload = $this->creationPayload(['ip_allocation' => 'fixed', 'ip_address' => '10.0.0.21']);
        unset($payload['concurrent_devices']);
        $this->postJson(route('subscriptions.store'), $payload)->assertSuccessful();
        $created = Subscription::query()->where('tenant_id', $this->subscription->tenant_id)->where('pppoe_username', 'new-guest')->firstOrFail();
        $this->assertSame(1, $created->concurrent_devices);
        $this->assertSame('10.0.0.21', $created->ip_address);
    }

    public function test_accounting_confirms_and_releases_only_the_matching_lease(): void
    {
        $lease = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-1');
        $url = '/api/v1/radius/'.$this->subscription->tenant_id.'/accounting';
        $payload = ['username' => 'guest', 'router_id' => $this->subscription->router_id, 'lease_token' => $lease->token];
        $this->withToken($this->token)->postJson($url, [...$payload, 'event' => 'start'])->assertOk();
        $this->assertNotNull($lease->fresh()->confirmed_at);
        $this->withToken($this->token)->postJson($url, [...$payload, 'event' => 'stop'])->assertOk();
        $this->assertNotNull($lease->fresh()->released_at);
    }

    public function test_disconnect_targets_every_concurrent_session(): void
    {
        $this->subscription->router->update(['coa_secret' => 'router-secret']);
        $first = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-1');
        $second = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'login-2');
        $this->allocator->confirm($this->subscription, $first->token);
        $this->allocator->confirm($this->subscription, $second->token);
        $this->mock(RouterOsCoaClient::class)->shouldReceive('disconnect')->twice()->andReturn(1);
        $result = app(SubscriptionSessionDisconnectService::class)->disconnect($this->subscription->fresh());
        $this->assertTrue($result->wasSuccessful());
        $this->assertNotNull($first->fresh()->released_at);
        $this->assertNotNull($second->fresh()->released_at);
    }

    public function test_expired_pending_allocations_are_released_by_the_scheduled_command(): void
    {
        $pending = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'pending');
        $confirmed = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'confirmed');
        $this->allocator->confirm($this->subscription, $confirmed->token);
        $this->travel(3)->minutes();
        $this->artisan('subscriptions:release-expired-ip-leases')->assertSuccessful();
        $this->assertNotNull($pending->fresh()->released_at);
        $this->assertNull($confirmed->fresh()->released_at);
    }

    public function test_delayed_router_reset_does_not_release_newer_sessions(): void
    {
        $old = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'old');
        $this->allocator->confirm($this->subscription, $old->token);
        $this->travel(1)->minutes();
        $resetAt = now()->toIso8601String();
        $this->travel(1)->minutes();
        $new = $this->allocator->allocate($this->subscription, $this->subscription->router_id, 'new');
        $this->allocator->confirm($this->subscription, $new->token);
        $this->withToken($this->token)->postJson('/api/v1/radius/'.$this->subscription->tenant_id.'/reset', [
            'router_id' => $this->subscription->router_id, 'reset_at' => $resetAt,
        ])->assertOk();
        $this->assertNotNull($old->fresh()->released_at);
        $this->assertNull($new->fresh()->released_at);
    }

    public function test_reserved_addresses_are_released_when_switching_back_to_single_device(): void
    {
        $this->put(route('subscriptions.update', $this->subscription), ['ip_allocation' => 'reserved_set', 'reserved_ip_addresses' => ['10.0.0.21', '10.0.0.22']])->assertRedirect();
        $this->put(route('subscriptions.update', $this->subscription), [
            'concurrent_devices' => 1, 'ip_allocation' => 'fixed', 'ip_address' => '10.0.0.21',
        ])->assertRedirect();
        $this->assertSame('10.0.0.21', $this->subscription->fresh()->ip_address);
        $this->assertSame([], $this->subscription->fresh()->reserved_ip_addresses);
        $this->assertDatabaseHas('ip_addresses', ['tenant_id' => $this->subscription->tenant_id, 'ip_address' => '10.0.0.22', 'status' => 'available']);
    }

    public function test_switching_to_automatic_allocation_releases_the_reserved_set_without_posting_hidden_fields(): void
    {
        $this->put(route('subscriptions.update', $this->subscription), ['ip_allocation' => 'reserved_set', 'reserved_ip_addresses' => ['10.0.0.21', '10.0.0.22']])->assertRedirect();
        $this->put(route('subscriptions.update', $this->subscription), ['ip_allocation' => 'dynamic_pool'])->assertRedirect();
        $this->assertSame([], $this->subscription->fresh()->reserved_ip_addresses);
        $this->assertDatabaseHas('ip_addresses', ['tenant_id' => $this->subscription->tenant_id, 'ip_address' => '10.0.0.21', 'status' => 'available']);
    }

    private function creationPayload(array $overrides = []): array
    {
        return [
            'customer_id' => $this->subscription->customer_id, 'name' => 'New guest access',
            'service_type' => 'hotspot', 'plan_id' => $this->subscription->plan_id, 'router_id' => $this->subscription->router_id,
            'connection_type' => 'pppoe', 'pppoe_username' => 'new-guest', 'pppoe_password' => 'password',
            'ip_management' => 'system', 'ip_pool_id' => $this->pool->id, 'concurrent_devices' => 2,
            'ip_allocation' => 'dynamic_pool', 'status' => 'pending', 'billing_enabled' => false,
            'items' => [['item_type' => 'plan', 'description' => 'Guest plan', 'quantity' => 1, 'unit_price' => 0, 'recurring' => true]],
            ...$overrides,
        ];
    }

    public function test_unconfigured_multi_device_subscriptions_fail_closed(): void
    {
        config(['radius_allocation.tokens' => []]);
        $this->subscription->update(['notes' => 'Resync']);
        $this->assertDatabaseHas('radcheck', ['tenant_id' => $this->subscription->tenant_id, 'username' => 'guest', 'attribute' => 'Auth-Type', 'value' => 'Reject']);
    }
}
