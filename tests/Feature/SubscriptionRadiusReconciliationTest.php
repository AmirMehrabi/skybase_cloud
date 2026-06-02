<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\RadiusCheck;
use App\Models\RadiusReply;
use App\Models\RadiusUserGroup;
use App\Models\Router;
use App\Models\Subscription;
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
            ->expectsOutputToContain('Processed: 1, active: 1, suspended: 0, failed: 0')
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
            ->expectsOutputToContain('Processed: 1, active: 0, suspended: 1, failed: 0')
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

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-'.Str::upper(Str::random(6)),
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
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
