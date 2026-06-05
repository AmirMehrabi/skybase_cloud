<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\RadiusCheck;
use App\Models\RadiusReply;
use App\Models\RadiusUserGroup;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionIpRoute;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionRadiusReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_restores_active_subscription_radius_rows(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription();

        $this->wipeRadiusRows($tenant->id, $subscription->pppoe_username);

        RadiusCheck::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Auth-Type',
            'op' => ':=',
            'value' => 'Reject',
        ]);

        $this->artisan('subscriptions:reconcile-radius-state')
            ->expectsOutputToContain('Processed: 1, active: 1, suspended: 0, skipped: 0, failed: 0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Cleartext-Password',
            'value' => 'secret-pass',
        ]);

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'NT-Password',
        ]);

        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Mikrotik-Rate-Limit',
        ]);

        $this->assertDatabaseHas('radusergroup', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
        ]);

        $this->assertDatabaseMissing('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Auth-Type',
            'value' => 'Reject',
        ]);
    }

    public function test_command_restores_suspended_subscription_radius_rows(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription();

        $subscription->suspend();

        RadiusCheck::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Cleartext-Password',
            'op' => ':=',
            'value' => 'secret-pass',
        ]);

        RadiusCheck::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'NT-Password',
            'op' => ':=',
            'value' => strtoupper(hash('md4', mb_convert_encoding('secret-pass', 'UTF-16LE', 'UTF-8'))),
        ]);

        RadiusReply::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Mikrotik-Rate-Limit',
            'op' => ':=',
            'value' => '20M/100M',
        ]);

        RadiusUserGroup::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'groupname' => 'skybase-plan-fiber-100',
            'priority' => 1,
        ]);

        $this->artisan('subscriptions:reconcile-radius-state')
            ->expectsOutputToContain('Processed: 1, active: 0, suspended: 1, skipped: 1, failed: 0')
            ->assertExitCode(0);

        $this->assertSame(0, RadiusCheck::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', $subscription->pppoe_username)
            ->whereIn('attribute', ['Cleartext-Password', 'NT-Password'])
            ->count());

        $this->assertSame(0, RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', $subscription->pppoe_username)
            ->count());

        $this->assertSame(0, RadiusUserGroup::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', $subscription->pppoe_username)
            ->count());

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Auth-Type',
            'op' => ':=',
            'value' => 'Reject',
        ]);
    }

    public function test_command_restores_active_subscription_ip_route_radius_rows(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription();

        SubscriptionIpRoute::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'ip_pool_id' => $subscription->ip_pool_id,
            'ip_address' => '192.168.50.0',
            'cidr' => 24,
            'routeros_sync_status' => 'failed',
            'routeros_sync_error' => 'Missing Framed-Route row.',
        ]);
        SubscriptionIpRoute::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'ip_pool_id' => $subscription->ip_pool_id,
            'ip_address' => '10.20.30.0',
            'cidr' => 24,
            'routeros_sync_status' => 'failed',
            'routeros_sync_error' => 'Missing Framed-Route row.',
        ]);

        $this->wipeRadiusRows($tenant->id, $subscription->pppoe_username);

        RadiusReply::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '203.0.113.0/24 172.16.120.33 1',
        ]);

        $this->artisan('subscriptions:reconcile-radius-state')
            ->expectsOutputToContain('Processed: 1, active: 1, suspended: 0, skipped: 0, failed: 0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Framed-IP-Address',
            'op' => ':=',
            'value' => '172.16.120.33',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '192.168.50.0/24 172.16.120.33 1',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '10.20.30.0/24 172.16.120.33 1',
        ]);
        $this->assertDatabaseMissing('radreply', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Framed-Route',
            'value' => '203.0.113.0/24 172.16.120.33 1',
        ]);
        $this->assertSame(2, RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', $subscription->pppoe_username)
            ->where('attribute', 'Framed-Route')
            ->count());
        $this->assertSame(2, SubscriptionIpRoute::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('subscription_id', $subscription->id)
            ->where('routeros_sync_status', 'synced')
            ->whereNull('routeros_sync_error')
            ->count());
    }

    public function test_command_removes_suspended_subscription_ip_route_radius_rows(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription();

        SubscriptionIpRoute::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'ip_pool_id' => $subscription->ip_pool_id,
            'ip_address' => '192.168.50.0',
            'cidr' => 24,
            'routeros_sync_status' => 'synced',
        ]);

        $subscription->suspend();

        RadiusReply::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '192.168.50.0/24 172.16.120.33 1',
        ]);

        $this->artisan('subscriptions:reconcile-radius-state')
            ->expectsOutputToContain('Processed: 1, active: 0, suspended: 1, skipped: 1, failed: 0')
            ->assertExitCode(0);

        $this->assertSame(0, RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', $subscription->pppoe_username)
            ->where('attribute', 'Framed-Route')
            ->count());
    }

    /**
     * @return array{0: Tenant, 1: Subscription}
     */
    private function createPppoeSubscription(): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Alpha Net',
            'slug' => 'alpha-net',
            'company_name' => 'Alpha Net',
            'email' => 'alpha@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-'.Str::upper(Str::random(8)),
            'customer_type' => 'individual',
            'first_name' => 'Alpha',
            'last_name' => 'User',
            'name' => 'Alpha User',
            'email' => Str::random(8).'@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Fiber 100',
            'internal_name' => 'fiber_100',
            'router_profile' => 'fiber_100',
            'status' => 'active',
            'type' => 'pppoe',
            'download_speed' => 100,
            'upload_speed' => 20,
            'bandwidth_unit' => 'Mbps',
            'price' => 79.99,
            'billing_cycle' => 'monthly',
        ]);

        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'vendor' => 'Mikrotik',
            'enable_provisioning' => true,
            'api_username' => 'admin',
            'api_password' => 'secret',
        ]);
        $pool = IpPool::create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'name' => 'Alpha Pool',
            'network_address' => '172.16.120.0',
            'cidr' => 24,
            'gateway' => '172.16.120.1',
            'type' => 'static',
            'status' => 'active',
            'allow_static' => true,
            'auto_assign' => true,
            'block_reserved' => false,
            'total_ips' => 254,
            'used_ips' => 0,
            'reserved_ips' => 0,
            'available_ips' => 254,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-'.Str::upper(Str::random(6)),
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'ip_address' => '172.16.120.33',
            'ip_pool_id' => $pool->id,
            'ip_management' => 'system',
            'pppoe_username' => 'alpha.user',
            'pppoe_password' => 'secret-pass',
            'base_price' => 79.99,
            'total_price' => 79.99,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now(),
        ]);

        return [$tenant, $subscription];
    }

    private function wipeRadiusRows(string $tenantId, string $username): void
    {
        RadiusCheck::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('username', $username)
            ->delete();

        RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('username', $username)
            ->delete();

        RadiusUserGroup::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('username', $username)
            ->delete();
    }
}
