<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionIpRoute;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RouterOs\RouterOsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_uses_the_subscription_status_from_the_model(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-TEST-0001',
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        $plan = Plan::factory()->create([
            'status' => 'active',
            'name' => 'Fiber 100',
            'price' => 79.99,
            'billing_cycle' => 'monthly',
        ]);

        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Core Router',
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-TEST-0001',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'site' => 'Downtown Office',
            'connection_type' => 'pppoe',
            'ip_address' => '192.168.1.100',
            'pppoe_username' => 'jane.doe',
            'pppoe_password' => 'secret',
            'base_price' => 79.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 79.99,
            'billing_cycle' => 'monthly',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-TEST-0001',
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'issue_date' => now()->subDays(7)->toDateString(),
            'due_date' => now()->addDays(23)->toDateString(),
            'subtotal' => 79.99,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 79.99,
            'paid_amount' => 54.99,
            'balance_due' => 25.00,
            'status' => 'partially_paid',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-TEST-0001',
            'amount' => 54.99,
            'payment_method' => 'cash',
            'status' => 'completed',
            'paid_at' => now()->subDays(2),
        ]);

        DB::table('radacct')->insert([
            'acctsessionid' => 'session-001',
            'acctuniqueid' => 'unique-001',
            'username' => 'jane.doe',
            'nasipaddress' => $router->ip_address,
            'acctstarttime' => now()->subHours(6),
            'acctupdatetime' => now()->subHours(4),
            'acctstoptime' => now()->subHours(4),
            'acctsessiontime' => 5400,
            'acctinputoctets' => 1073741824,
            'acctoutputoctets' => 2147483648,
            'framedipaddress' => '192.168.1.100',
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.show', $subscription));

        $response->assertOk();
        $response->assertSee('Pending');
        $response->assertDontSee('Suspend');
        $response->assertSee('Activate');
        $response->assertSee('INV-TEST-0001');
        $response->assertSee('3.00 GB');
        $response->assertViewHas('subscription', function (mixed $viewSubscription): bool {
            return $viewSubscription instanceof Subscription
                && $viewSubscription->status === 'pending'
                && $viewSubscription->subscription_code === 'SUB-TEST-0001';
        });
        $response->assertViewHas('billingInvoices', function (mixed $billingInvoices): bool {
            return is_array($billingInvoices)
                && count($billingInvoices) === 1
                && $billingInvoices[0]['invoice_number'] === 'INV-TEST-0001'
                && (float) $billingInvoices[0]['balance_due'] === 25.0;
        });
        $response->assertViewHas('usageSummary', function (mixed $usageSummary): bool {
            return is_array($usageSummary)
                && (float) $usageSummary['total_gb'] === 3.0
                && (int) $usageSummary['sessions'] === 1;
        });
        $response->assertViewHas('usageSessions', function (mixed $usageSessions): bool {
            return is_array($usageSessions)
                && count($usageSessions) === 1
                && $usageSessions[0]['download'] === '2.00 GB'
                && $usageSessions[0]['upload'] === '1.00 GB';
        });
    }

    public function test_show_and_edit_pages_expose_ip_change_actions(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();

        $showResponse = $this->actingAs($user)->get(route('subscriptions.show', $subscription));
        $showResponse->assertOk();
        $showResponse->assertSee('Change IP');
        $showResponse->assertSee('System managed');

        $editResponse = $this->actingAs($user)->get(route('subscriptions.edit', $subscription));
        $editResponse->assertOk();
        $editResponse->assertSee('Suggest free IP');
        $editResponse->assertSee('IP Address');
    }

    public function test_suggest_ip_returns_the_next_free_ip_in_the_current_pool(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();

        $response = $this->actingAs($user)->getJson(route('subscriptions.suggest-ip', $subscription));

        $response->assertOk();
        $response->assertJson([
            'ip_address' => '10.10.0.12',
            'pool_name' => 'Core Pool',
        ]);
    }

    public function test_update_moves_a_system_managed_subscription_to_a_new_free_ip(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();

        $response = $this->actingAs($user)->put(route('subscriptions.update', $subscription), [
            'ip_address' => '10.10.0.12',
        ]);

        $response->assertRedirect(route('subscriptions.show', $subscription));
        $response->assertSessionHas('success', 'Subscription updated successfully.');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'ip_address' => '10.10.0.12',
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_address' => '10.10.0.11',
            'status' => 'available',
            'subscription_code' => null,
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_address' => '10.10.0.12',
            'status' => 'assigned',
            'subscription_code' => $subscription->subscription_code,
        ]);
    }

    public function test_store_creates_system_managed_ip_routes_and_syncs_routeros_routes(): void
    {
        [$tenant, $user, $customer, $plan, $router, $pool] = $this->createSubscriptionCreateDependencies();
        $routerOs = new class extends RouterOsClient
        {
            public array $sentences = [];

            private array $routes = [];

            private array $lastSentence = [];

            public function execute(Router $router, callable $callback): mixed
            {
                return $callback(null, $this);
            }

            public function writeSentence($connection, array $words): void
            {
                $this->sentences[] = $words;
                $this->lastSentence = $words;

                if (($words[0] ?? null) !== '/ip/route/add') {
                    return;
                }

                $this->routes[] = [
                    '.id' => '*'.count($this->routes),
                    'dst-address' => $this->sentenceValue($words, 'dst-address'),
                    'gateway' => $this->sentenceValue($words, 'gateway'),
                    'comment' => $this->sentenceValue($words, 'comment'),
                ];
            }

            public function readResponse($connection): array
            {
                if (($this->lastSentence[0] ?? null) !== '/ip/route/print') {
                    return [];
                }

                $comment = collect($this->lastSentence)
                    ->first(fn (string $word): bool => str_starts_with($word, '?comment='));

                if (! $comment) {
                    return $this->routes;
                }

                $comment = substr($comment, strlen('?comment='));

                return collect($this->routes)
                    ->filter(fn (array $route): bool => $route['comment'] === $comment)
                    ->values()
                    ->all();
            }

            private function sentenceValue(array $words, string $key): ?string
            {
                $word = collect($words)->first(fn (string $word): bool => str_starts_with($word, "={$key}="));

                return $word ? substr($word, strlen("={$key}=")) : null;
            }
        };
        $this->app->instance(RouterOsClient::class, $routerOs);

        IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '10.10.0.11',
            'status' => 'available',
        ]);
        IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '10.10.0.12',
            'status' => 'available',
        ]);
        $pool->updateStatistics();

        $response = $this->actingAs($user)->postJson(route('subscriptions.store'), [
            'customer_id' => $customer->id,
            'name' => 'John Doe Routed Service',
            'service_type' => 'pppoe',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'john.routed',
            'pppoe_password' => 'secret-pass',
            'ip_management' => 'system',
            'ip_pool_id' => $pool->id,
            'ip_address' => '10.10.0.11',
            'ip_routes' => [
                ['ip_pool_id' => $pool->id, 'ip_address' => '10.10.0.12', 'cidr' => 32],
            ],
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'items' => [
                [
                    'item_type' => 'plan',
                    'description' => 'Fiber 200',
                    'quantity' => 1,
                    'unit_price' => 99.99,
                    'discount_amount' => 0,
                    'discount_type' => 'none',
                    'tax_percentage' => 0,
                    'recurring' => true,
                    'billing_cycle' => 'monthly',
                ],
            ],
        ]);

        $response->assertCreated();

        $subscription = Subscription::query()->where('pppoe_username', 'john.routed')->firstOrFail();

        $this->assertDatabaseHas('subscription_ip_routes', [
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '10.10.0.12',
            'cidr' => 32,
            'routeros_sync_status' => 'synced',
        ]);
        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_address' => '10.10.0.12',
            'status' => 'assigned',
            'customer_id' => $customer->id,
            'subscription_code' => null,
        ]);
        $this->assertTrue(collect($routerOs->sentences)->contains(fn (array $sentence): bool => $sentence === [
            '/ip/route/add',
            '=dst-address=10.10.0.12/32',
            '=gateway=10.10.0.11',
            '=comment=skybase:subscription-ip-route:'.$subscription->ipRoutes()->firstOrFail()->id,
        ]));
    }

    public function test_update_primary_ip_resyncs_existing_ip_route_gateway(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();
        $routerOs = new class extends RouterOsClient
        {
            public array $sentences = [];

            private array $lastSentence = [];

            public function execute(Router $router, callable $callback): mixed
            {
                return $callback(null, $this);
            }

            public function writeSentence($connection, array $words): void
            {
                $this->sentences[] = $words;
                $this->lastSentence = $words;
            }

            public function readResponse($connection): array
            {
                if (($this->lastSentence[0] ?? null) === '/ip/route/print') {
                    $comment = collect($this->lastSentence)
                        ->first(fn (string $word): bool => str_starts_with($word, '?comment='));

                    return [[
                        '.id' => '*1',
                        'comment' => $comment ? substr($comment, strlen('?comment=')) : null,
                    ]];
                }

                return [];
            }
        };
        $this->app->instance(RouterOsClient::class, $routerOs);

        $routeIp = IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $subscription->ip_pool_id,
            'ip_address' => '10.10.0.13',
            'status' => 'assigned',
            'customer_id' => $subscription->customer_id,
            'metadata' => ['purpose' => 'subscription_ip_route'],
        ]);
        $ipRoute = SubscriptionIpRoute::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'ip_pool_id' => $subscription->ip_pool_id,
            'ip_address_id' => $routeIp->id,
            'ip_address' => '10.10.0.13',
            'cidr' => 32,
            'routeros_comment' => 'skybase:subscription-ip-route:1',
            'routeros_sync_status' => 'synced',
        ]);

        $response = $this->actingAs($user)->put(route('subscriptions.update', $subscription), [
            'ip_address' => '10.10.0.12',
        ]);

        $response->assertRedirect(route('subscriptions.show', $subscription));

        $this->assertTrue(collect($routerOs->sentences)->contains(fn (array $sentence): bool => $sentence === [
            '/ip/route/set',
            '=.id=*1',
            '=dst-address=10.10.0.13/32',
            '=gateway=10.10.0.12',
            '=comment='.$ipRoute->routeros_comment,
        ]));
    }

    private function createTenant(string $slug, string $companyName): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => $companyName,
            'slug' => $slug,
            'company_name' => $companyName,
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: Customer, 3: Plan, 4: Router, 5: IpPool}
     */
    private function createSubscriptionCreateDependencies(): array
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-TEST-0003',
            'customer_type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Routed',
            'name' => 'John Routed',
            'email' => 'john.routed@example.com',
            'mobile' => '555-0103',
            'address_line1' => '789 Main Street',
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
            'status' => 'active',
            'name' => 'Fiber 200',
            'price' => 99.99,
            'billing_cycle' => 'monthly',
            'download_speed' => 100,
            'upload_speed' => 20,
        ]);

        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Edge Router',
            'vendor' => 'Mikrotik',
            'enable_provisioning' => true,
            'api_username' => 'admin',
            'api_password' => 'secret',
        ]);

        $pool = IpPool::create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'name' => 'Core Pool',
            'network_address' => '10.10.0.0',
            'cidr' => 24,
            'gateway' => '10.10.0.1',
            'type' => 'static',
            'status' => 'active',
            'allow_static' => true,
            'auto_assign' => true,
            'block_reserved' => false,
            'site' => 'Head Office',
        ]);

        return [$tenant, $user, $customer, $plan, $router, $pool];
    }

    /**
     * @return array{0: Tenant, 1: User, 2: Subscription}
     */
    private function createSystemManagedSubscriptionWithPool(): array
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-TEST-0002',
            'customer_type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'mobile' => '555-0102',
            'address_line1' => '456 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        $plan = Plan::factory()->create([
            'status' => 'active',
            'name' => 'Fiber 200',
            'price' => 99.99,
            'billing_cycle' => 'monthly',
        ]);

        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Edge Router',
            'vendor' => 'Mikrotik',
            'enable_provisioning' => true,
            'api_username' => 'admin',
            'api_password' => 'secret',
        ]);

        $pool = IpPool::create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'name' => 'Core Pool',
            'network_address' => '10.10.0.0',
            'cidr' => 24,
            'gateway' => '10.10.0.1',
            'type' => 'static',
            'status' => 'active',
            'allow_static' => true,
            'auto_assign' => true,
            'block_reserved' => false,
            'site' => 'Head Office',
            'total_ips' => 3,
            'used_ips' => 0,
            'reserved_ips' => 0,
            'available_ips' => 0,
        ]);

        IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '10.10.0.11',
            'status' => 'assigned',
            'customer_id' => $customer->id,
            'mac_address' => 'AA:BB:CC:DD:EE:11',
            'subscription_code' => 'SUB-TEST-0002',
            'assigned_at' => now(),
        ]);

        IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '10.10.0.12',
            'status' => 'available',
        ]);

        $pool->updateStatistics();

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-TEST-0002',
            'name' => 'John Doe Service',
            'service_type' => 'pppoe',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'site' => 'Head Office',
            'connection_type' => 'static',
            'ip_address' => '10.10.0.11',
            'ip_pool_id' => $pool->id,
            'ip_management' => 'system',
            'base_price' => 99.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 99.99,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'grace_period_days' => 7,
            'status' => 'active',
            'start_date' => now(),
            'activation_date' => now(),
            'next_billing_date' => now()->addMonth()->toDateString(),
        ]);

        return [$tenant, $user, $subscription];
    }
}
