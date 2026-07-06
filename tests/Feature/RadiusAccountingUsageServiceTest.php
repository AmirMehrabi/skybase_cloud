<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\RadiusAccountingUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RadiusAccountingUsageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_radius_accounting_sessions_to_tenant_subscriptions_by_pppoe_username(): void
    {
        $tenant = $this->createTenant();
        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-ALPHA',
            'customer_type' => 'individual',
            'first_name' => 'Alpha',
            'last_name' => 'Customer',
            'name' => 'Alpha Customer',
            'email' => 'customer@example.com',
            'mobile' => '555-0100',
            'address_line1' => '10 Main Street',
            'city' => 'Tehran',
            'country' => 'Iran',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
            'password' => 'password123',
        ]);
        $plan = Plan::factory()->create(['status' => 'active', 'data_limit' => 100, 'data_unit' => 'GB', 'unlimited' => false]);
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'ip_address' => '10.0.0.1']);
        $subscription = Subscription::withoutEvents(fn (): Subscription => Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-'.Str::upper(Str::random(6)),
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'alpha.user',
            'pppoe_password' => 'secret',
            'base_price' => 50,
            'total_price' => 50,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
        ]));

        DB::table('radacct')->insert([
            'acctsessionid' => 'session-001',
            'acctuniqueid' => 'unique-001',
            'username' => 'alpha.user',
            'nasipaddress' => '10.0.0.1',
            'acctstarttime' => now()->subMinutes(30),
            'acctupdatetime' => now()->subMinutes(5),
            'acctstoptime' => null,
            'acctsessiontime' => 1500,
            'acctinputoctets' => 1024,
            'acctoutputoctets' => 2048,
            'framedipaddress' => '172.16.0.10',
        ]);

        DB::table('radacct')->insert([
            'acctsessionid' => 'session-002',
            'acctuniqueid' => 'unique-002',
            'username' => 'other.tenant',
            'acctstarttime' => now()->subMinutes(30),
            'acctupdatetime' => now()->subMinutes(5),
            'acctstoptime' => null,
            'acctinputoctets' => 999999,
            'acctoutputoctets' => 999999,
        ]);

        $sessions = app(RadiusAccountingUsageService::class)->sessionsForTenant($tenant->id, now()->subDay(), now());

        $this->assertCount(1, $sessions);
        $this->assertSame($subscription->id, $sessions->first()['subscription_id']);
        $this->assertSame('online', $sessions->first()['status']);
        $this->assertSame(2048, $sessions->first()['download']);
        $this->assertSame(1024, $sessions->first()['upload']);
        $this->assertSame('172.16.0.10', $sessions->first()['ip_address']);

        $dailyUsage = app(RadiusAccountingUsageService::class)->dailyUsageForTenant($tenant->id, now()->subDay(), now());

        $this->assertCount(1, $dailyUsage);
        $this->assertSame($subscription->id, $dailyUsage->first()['subscription_id']);
        $this->assertSame(2048, $dailyUsage->first()['download']);
        $this->assertSame(1024, $dailyUsage->first()['upload']);
        $this->assertSame(1, $dailyUsage->first()['sessions']);
        $this->assertSame(1, $dailyUsage->first()['online_sessions']);
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Alpha Net',
            'slug' => 'alpha-net',
            'company_name' => 'Alpha Net',
            'email' => 'alpha@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }
}
