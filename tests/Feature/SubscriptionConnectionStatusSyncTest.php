<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\RadiusAccountingRecord;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionConnectionStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_marks_pppoe_subscriptions_online_when_an_open_radius_session_exists(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription();

        RadiusAccountingRecord::create([
            'acctsessionid' => 'session-001',
            'acctuniqueid' => 'unique-001',
            'username' => $subscription->pppoe_username,
            'acctstarttime' => now()->subMinutes(10),
            'acctupdatetime' => now(),
            'acctstoptime' => null,
            'acctsessiontime' => 600,
            'acctinputoctets' => 1024,
            'acctoutputoctets' => 2048,
        ]);

        $this->artisan('subscriptions:sync-connection-status')
            ->assertExitCode(0);

        $subscription->refresh();

        $this->assertSame('online', $subscription->connection_status);
        $this->assertNotNull($subscription->connection_status_checked_at);
    }

    public function test_sync_clears_connection_status_for_non_pppoe_subscriptions(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription();

        $subscription->update([
            'connection_type' => 'dhcp',
            'connection_status' => 'online',
            'connection_status_checked_at' => now()->subHour(),
        ]);

        $this->artisan('subscriptions:sync-connection-status')
            ->assertExitCode(0);

        $subscription->refresh();

        $this->assertNull($subscription->connection_status);
        $this->assertNotNull($subscription->connection_status_checked_at);
    }

    /**
     * @return array{0: Tenant, 1: Subscription}
     */
    private function createPppoeSubscription(): array
    {
        $tenant = $this->createTenant('alpha-net');

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'active',
            'billing_enabled' => true,
        ]);

        $plan = Plan::factory()->create([
            'status' => 'active',
        ]);

        $router = Router::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'online',
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
            'base_price' => 50,
            'total_price' => 50,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
        ]);

        return [$tenant, $subscription];
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => Str::headline($slug),
            'slug' => $slug,
            'company_name' => Str::headline($slug),
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }
}
