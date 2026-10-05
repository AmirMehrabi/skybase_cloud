<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\NetworkAlert;
use App\Models\NetworkUsageRecord;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\NetworkMonitoringSeeder;
use Database\Seeders\SiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_tenant_and_attaches_the_test_user_as_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->first();
        $user = User::query()->where('tenant_id', $tenant->id)->where('email', 'test@example.com')->first();

        $this->assertNotNull($tenant);
        $this->assertNotNull($user);
        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertSame('admin', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('password1@1@', $user->password));
    }

    public function test_sites_are_seeded_per_tenant_without_replacing_existing_sites(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->where('slug', 'test-tenant')->firstOrFail();
        $otherTenant = Tenant::query()->create([
            'id' => 'other-tenant',
            'slug' => 'other-tenant',
            'name' => 'Other Tenant',
            'company_name' => 'Other Tenant',
            'email' => 'other@example.com',
            'status' => 'active',
        ]);
        $existingSite = Site::factory()->active()->create(['tenant_id' => $otherTenant->id]);
        $router = Router::factory()->offline()->create(['tenant_id' => $otherTenant->id]);

        $this->seed(SiteSeeder::class);
        $this->seed(SiteSeeder::class);

        $this->assertSame(3, Site::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, Site::query()->where('tenant_id', $otherTenant->id)->count());
        $this->assertSame($existingSite->id, $router->fresh()->site_id);
    }

    public function test_monitoring_seeds_stay_within_each_tenant_and_can_be_repeated(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->where('slug', 'test-tenant')->firstOrFail();
        $otherTenant = Tenant::query()->create([
            'id' => 'other-tenant',
            'slug' => 'other-tenant',
            'name' => 'Other Tenant',
            'company_name' => 'Other Tenant',
            'email' => 'other@example.com',
            'status' => 'active',
        ]);
        Plan::factory()->create(['tenant_id' => $otherTenant->id, 'status' => 'active']);
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);
        $customer = $this->createCustomer($tenant, 'active');
        $router = Router::factory()->offline()->create([
            'tenant_id' => $tenant->id,
            'cpu_usage' => 80,
        ]);
        $otherRouter = Router::factory()->online()->create([
            'tenant_id' => $otherTenant->id,
            'cpu_usage' => 10,
            'memory_usage' => 10,
        ]);
        $otherCustomer = $this->createCustomer($otherTenant, 'inactive');
        Plan::query()->where('tenant_id', $otherTenant->id)->delete();

        $this->seed(NetworkMonitoringSeeder::class);
        $this->seed(NetworkMonitoringSeeder::class);

        $subscription = Subscription::query()->where('tenant_id', $tenant->id)->sole();
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame($customer->id, $subscription->customer_id);
        $this->assertSame($router->id, $subscription->router_id);
        $this->assertSame(20, strlen($subscription->pppoe_password));
        $this->assertSame(1, NetworkUsageRecord::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, NetworkAlert::query()->where('tenant_id', $tenant->id)->count());
        $otherSubscription = Subscription::query()->where('tenant_id', $otherTenant->id)->sole();
        $this->assertNull($otherSubscription->plan_id);
        $this->assertSame($otherCustomer->id, $otherSubscription->customer_id);
        $this->assertSame($otherRouter->id, $otherSubscription->router_id);
        $this->assertSame('pending', $otherSubscription->status);
        $this->assertSame(1, NetworkUsageRecord::query()->where('tenant_id', $otherTenant->id)->count());
        $this->assertSame(0, NetworkAlert::query()->where('tenant_id', $otherTenant->id)->count());
    }

    private function createCustomer(Tenant $tenant, string $status): Customer
    {
        $attributes = Customer::factory()->make(['tenant_id' => $tenant->id, 'status' => $status])->getAttributes();

        return Customer::query()->create(array_diff_key($attributes, array_flip([
            'plan', 'plan_id', 'site', 'router', 'router_id', 'ip_address',
            'pppoe_username', 'pppoe_password', 'billing_cycle', 'activation_date',
        ])));
    }
}
