<?php

namespace Tests\Feature;

use App\Jobs\BulkDeleteModelsJob;
use App\Jobs\Subscriptions\ActivateSubscriptionJob;
use App\Jobs\Subscriptions\SuspendSubscriptionJob;
use App\Models\BulkDeletionRun;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\RadiusPostAuthRecord;
use App\Models\RadiusReply;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionIpRoute;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BulkDeletionService;
use App\Services\RouterOs\RouterOsClient;
use App\Services\RouterOs\RouterOsCoaClient;
use App\Services\SubscriptionSessionDisconnectResult;
use App\Services\SubscriptionSessionDisconnectService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
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

    public function test_show_page_renders_recent_radius_auth_attempts_with_pagination(): void
    {
        [$tenant, $user, $subscription] = $this->createRadiusAuthSubscription();

        foreach (range(1, 11) as $index) {
            RadiusPostAuthRecord::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'username' => $subscription->pppoe_username,
                'pass' => 'secret-'.$index,
                'reply' => $index % 2 === 0 ? "Access-Accept\nReply-Message = Welcome back" : "Access-Reject\nReply-Message = Invalid password",
                'authdate' => now()->subMinutes($index),
            ]);
        }

        $response = $this->actingAs($user)->get(route('subscriptions.show', [
            'subscription' => $subscription,
            'tab' => 'auth',
            'radpostauth_page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('Recent RADIUS Auth Attempts');
        $response->assertSee('Entries older than 20 minutes are pruned automatically.');
        $response->assertViewHas('authAttempts', function (mixed $authAttempts): bool {
            return $authAttempts->total() === 11
                && $authAttempts->perPage() === 10
                && $authAttempts->currentPage() === 2
                && $authAttempts->count() === 1;
        });
    }

    public function test_model_prune_removes_radius_post_auth_attempts_older_than_twenty_minutes(): void
    {
        [$tenant, $user, $subscription] = $this->createRadiusAuthSubscription();

        RadiusPostAuthRecord::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'pass' => 'old-secret',
            'reply' => 'Access-Reject',
            'authdate' => now()->subMinutes(21),
        ]);

        RadiusPostAuthRecord::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'pass' => 'recent-secret',
            'reply' => 'Access-Accept',
            'authdate' => now()->subMinutes(5),
        ]);

        $this->artisan('model:prune', [
            '--model' => [RadiusPostAuthRecord::class],
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('radpostauth', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'pass' => 'old-secret',
        ]);

        $this->assertDatabaseHas('radpostauth', [
            'tenant_id' => $tenant->id,
            'username' => $subscription->pppoe_username,
            'pass' => 'recent-secret',
        ]);
    }

    public function test_show_and_edit_pages_expose_ip_change_actions(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();
        $subscription->forceFill([
            'connection_type' => 'pppoe',
            'pppoe_username' => 'john.doe',
            'pppoe_password' => 'secret-pass',
        ])->saveQuietly();

        $showResponse = $this->actingAs($user)->get(route('subscriptions.show', $subscription));
        $showResponse->assertOk();
        $showResponse->assertSee('Change IP');
        $showResponse->assertSee('Kill');
        $showResponse->assertSee('System managed');
        $showResponse->assertSee('IP Pool Assignment');

        $editResponse = $this->actingAs($user)->get(route('subscriptions.edit', $subscription));
        $editResponse->assertOk();
        $editResponse->assertSee('Suggest free IP');
        $editResponse->assertSee('IP Pool Assignment');
        $editResponse->assertSee('IP Route');
    }

    public function test_edit_page_allows_selecting_a_pool_when_no_pool_is_assigned(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();
        $subscription->forceFill([
            'ip_pool_id' => null,
        ])->saveQuietly();

        $subscription->refresh();

        $response = $this->actingAs($user)->get(route('subscriptions.edit', $subscription));

        $response->assertOk();
        $response->assertSee('IP Pool Assignment');
        $response->assertSee('Select IP Pool');
        $response->assertDontSee('Manual IP Address');
        $response->assertSee('Select a pool for this router, then choose the primary IP from that pool.');
        $response->assertViewHas('currentIpPoolId', (string) $subscription->ipAddress?->ip_pool_id);
        $response->assertViewHas('currentIpAddress', '10.10.0.11');
    }

    public function test_kill_session_route_disconnects_via_routeros_api(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();
        $subscription->forceFill([
            'connection_type' => 'pppoe',
            'pppoe_username' => 'john.doe',
            'pppoe_password' => 'secret-pass',
        ])->saveQuietly();
        $subscription->forceFill([
            'connection_status' => 'online',
            'connection_status_checked_at' => now()->subMinutes(5),
        ])->saveQuietly();

        DB::table('radacct')->insert([
            'acctsessionid' => '8120002f',
            'acctuniqueid' => 'unique-8120002f',
            'username' => 'john.doe',
            'nasipaddress' => $subscription->router->ip_address,
            'acctstarttime' => now()->subMinutes(30),
            'acctupdatetime' => now()->subMinutes(10),
            'acctstoptime' => null,
            'acctsessiontime' => 1800,
            'framedipaddress' => $subscription->ip_address,
        ]);

        $this->app->instance(RouterOsClient::class, new class extends RouterOsClient
        {
            public function execute(Router $router, callable $callback, ?int $timeoutSeconds = null): mixed
            {
                return 1;
            }
        });

        $response = $this->actingAs($user)->post(route('subscriptions.kill-session', $subscription));

        $response
            ->assertRedirect(route('subscriptions.show', $subscription))
            ->assertSessionHas('success', 'Disconnected 1 active PPP session(s).');

        $subscription->refresh();

        $this->assertSame('offline', $subscription->connection_status);
        $this->assertNotNull($subscription->connection_status_checked_at);
        $this->assertDatabaseHas('activity_log', [
            'tenant_id' => $tenant->id,
            'subject_type' => Subscription::class,
            'subject_id' => $subscription->id,
            'event' => 'session_disconnect_succeeded',
        ]);
    }

    public function test_kill_session_route_falls_back_to_coa_when_api_disconnect_fails(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();
        $subscription->forceFill([
            'connection_type' => 'pppoe',
            'pppoe_username' => 'john.doe',
            'pppoe_password' => 'secret-pass',
        ])->saveQuietly();
        $subscription->router->forceFill([
            'coa_port' => 1700,
            'coa_secret' => 'coa-secret',
        ])->saveQuietly();
        $subscription->forceFill([
            'connection_status' => 'online',
            'connection_status_checked_at' => now()->subMinutes(5),
        ])->saveQuietly();

        $this->app->instance(RouterOsClient::class, new class extends RouterOsClient
        {
            public function execute(Router $router, callable $callback, ?int $timeoutSeconds = null): mixed
            {
                throw new \RuntimeException('API is unavailable.');
            }
        });

        $this->app->instance(RouterOsCoaClient::class, new class extends RouterOsCoaClient
        {
            public function disconnect(
                Router $router,
                string $username,
                ?string $acctSessionId = null,
                ?string $nasIpAddress = null,
                ?string $framedIpAddress = null,
                ?int $timeoutSeconds = null,
            ): int {
                return 1;
            }
        });

        $response = $this->actingAs($user)->post(route('subscriptions.kill-session', $subscription));

        $response
            ->assertRedirect(route('subscriptions.show', $subscription))
            ->assertSessionHas('success', 'Disconnected 1 active PPP session(s) via CoA after RouterOS API: RouterOS API disconnect failed: API is unavailable.');

        $subscription->refresh();

        $this->assertSame('offline', $subscription->connection_status);
        $this->assertDatabaseHas('activity_log', [
            'tenant_id' => $tenant->id,
            'subject_type' => Subscription::class,
            'subject_id' => $subscription->id,
            'event' => 'session_disconnect_succeeded',
        ]);
    }

    public function test_update_credentials_changes_username_and_disconnects_the_previous_router_session(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();
        $subscription->forceFill([
            'connection_type' => 'pppoe',
            'pppoe_username' => 'john.doe',
            'pppoe_password' => 'secret-pass',
            'connection_status' => 'online',
            'connection_status_checked_at' => now()->subMinutes(5),
        ])->saveQuietly();

        $tracker = (object) ['calls' => []];
        $this->bindSuccessfulCredentialDisconnectService($tracker);

        $response = $this->actingAs($user)->put(route('subscriptions.update', $subscription), [
            'pppoe_username' => 'john.renamed',
            'pppoe_password' => 'new-secret-pass',
        ]);

        $response->assertRedirect(route('subscriptions.show', $subscription));
        $response->assertSessionHas('success', 'Subscription credentials updated successfully. Disconnected 1 active PPP session(s).');

        $subscription->refresh();

        $this->assertSame('john.renamed', $subscription->pppoe_username);
        $this->assertSame('offline', $subscription->connection_status);
        $this->assertSame(['john.doe'], $tracker->calls);
        $this->assertDatabaseMissing('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'john.doe',
            'attribute' => 'Cleartext-Password',
        ]);
        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'john.renamed',
            'attribute' => 'Cleartext-Password',
            'value' => 'new-secret-pass',
        ]);
    }

    public function test_update_credentials_with_password_only_disconnects_the_current_router_session(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();
        $subscription->forceFill([
            'connection_type' => 'pppoe',
            'pppoe_username' => 'john.doe',
            'pppoe_password' => 'secret-pass',
            'connection_status' => 'online',
            'connection_status_checked_at' => now()->subMinutes(5),
        ])->saveQuietly();

        $tracker = (object) ['calls' => []];
        $this->bindSuccessfulCredentialDisconnectService($tracker);

        $response = $this->actingAs($user)->put(route('subscriptions.update', $subscription), [
            'pppoe_password' => 'new-secret-pass',
        ]);

        $response->assertRedirect(route('subscriptions.show', $subscription));
        $response->assertSessionHas('success', 'Subscription credentials updated successfully. Disconnected 1 active PPP session(s).');

        $subscription->refresh();

        $this->assertSame('john.doe', $subscription->pppoe_username);
        $this->assertSame('offline', $subscription->connection_status);
        $this->assertSame(['john.doe'], $tracker->calls);
        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'john.doe',
            'attribute' => 'Cleartext-Password',
            'value' => 'new-secret-pass',
        ]);
    }

    public function test_suspend_route_queues_background_processing_and_saves_status_quietly(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();

        Queue::fake();

        $response = $this->actingAs($user)->post(route('subscriptions.suspend', $subscription));

        $response->assertRedirect(route('subscriptions.show', $subscription));
        $response->assertSessionHas('success', 'Subscription suspension queued. Background processing will finish disconnecting the session and syncing RADIUS.');

        Queue::assertPushedOn('subscriptions', SuspendSubscriptionJob::class);

        Queue::assertPushed(SuspendSubscriptionJob::class, function (SuspendSubscriptionJob $job) use ($subscription, $tenant, $user): bool {
            return $job->subscriptionId === $subscription->id
                && $job->tenantId === $tenant->id
                && $job->causedByUserId === $user->id;
        });

        $subscription->refresh();

        $this->assertSame('suspended', $subscription->status);
        $this->assertNotNull($subscription->suspended_at);
    }

    public function test_activate_route_queues_background_processing_and_saves_status_quietly(): void
    {
        [$tenant, $user, $subscription] = $this->createPendingSystemManagedSubscriptionWithPool();

        Queue::fake();

        $response = $this->actingAs($user)->post(route('subscriptions.activate', $subscription));

        $response->assertRedirect(route('subscriptions.show', $subscription));
        $response->assertSessionHas('success', 'Subscription activation queued. Background processing will finish provisioning RADIUS and notifications.');

        Queue::assertPushedOn('subscriptions', ActivateSubscriptionJob::class);

        Queue::assertPushed(ActivateSubscriptionJob::class, function (ActivateSubscriptionJob $job) use ($subscription, $tenant): bool {
            return $job->subscriptionId === $subscription->id
                && $job->tenantId === $tenant->id;
        });

        $subscription->refresh();

        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->activation_date);
        $this->assertDatabaseMissing('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Cleartext-Password',
        ]);
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

    public function test_update_moves_a_system_managed_subscription_to_a_new_router_pool_and_ip(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();

        $newRouter = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Aggregation Router',
            'vendor' => 'Mikrotik',
            'enable_provisioning' => true,
            'api_username' => 'admin',
            'api_password' => 'secret',
        ]);

        $newPool = IpPool::create([
            'tenant_id' => $tenant->id,
            'router_id' => $newRouter->id,
            'name' => 'Aggregation Pool',
            'network_address' => '10.20.0.0',
            'cidr' => 24,
            'gateway' => '10.20.0.1',
            'type' => 'static',
            'status' => 'active',
            'allow_static' => true,
            'auto_assign' => true,
            'block_reserved' => false,
            'site' => 'Core Site',
        ]);

        IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $newPool->id,
            'ip_address' => '10.20.0.11',
            'status' => 'available',
        ]);
        $newPool->updateStatistics();

        $response = $this->actingAs($user)->put(route('subscriptions.update', $subscription), [
            'router_id' => $newRouter->id,
            'ip_pool_id' => $newPool->id,
            'ip_address' => '10.20.0.11',
        ]);

        $response->assertRedirect(route('subscriptions.show', $subscription));
        $response->assertSessionHas('success', 'Subscription updated successfully.');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'router_id' => $newRouter->id,
            'ip_pool_id' => $newPool->id,
            'ip_address' => '10.20.0.11',
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_address' => '10.10.0.11',
            'status' => 'available',
            'subscription_code' => null,
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_address' => '10.20.0.11',
            'status' => 'assigned',
            'subscription_code' => $subscription->subscription_code,
        ]);
    }

    public function test_update_rejects_an_ip_pool_that_does_not_belong_to_the_selected_router(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();

        $newRouter = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Aggregation Router',
            'vendor' => 'Mikrotik',
            'enable_provisioning' => true,
            'api_username' => 'admin',
            'api_password' => 'secret',
        ]);

        $response = $this->actingAs($user)->putJson(route('subscriptions.update', $subscription), [
            'router_id' => $newRouter->id,
            'ip_pool_id' => $subscription->ip_pool_id,
            'ip_address' => '10.10.0.11',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ip_pool_id']);
    }

    public function test_update_allows_an_all_devices_ip_pool_for_any_router(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();

        $newRouter = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Aggregation Router',
            'vendor' => 'Mikrotik',
            'enable_provisioning' => true,
            'api_username' => 'admin',
            'api_password' => 'secret',
        ]);

        $globalPool = IpPool::create([
            'tenant_id' => $tenant->id,
            'router_id' => null,
            'name' => 'Global Pool',
            'network_address' => '10.30.0.0',
            'cidr' => 24,
            'gateway' => '10.30.0.1',
            'type' => 'static',
            'status' => 'active',
            'allow_static' => true,
            'auto_assign' => true,
            'block_reserved' => false,
            'site' => 'Core Site',
            'all_devices' => true,
        ]);

        IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $globalPool->id,
            'ip_address' => '10.30.0.11',
            'status' => 'available',
        ]);
        $globalPool->updateStatistics();

        $response = $this->actingAs($user)->put(route('subscriptions.update', $subscription), [
            'router_id' => $newRouter->id,
            'ip_pool_id' => $globalPool->id,
            'ip_address' => '10.30.0.11',
        ]);

        $response->assertRedirect(route('subscriptions.show', $subscription));
        $response->assertSessionHas('success', 'Subscription updated successfully.');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'router_id' => $newRouter->id,
            'ip_pool_id' => $globalPool->id,
            'ip_address' => '10.30.0.11',
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_address' => '10.30.0.11',
            'status' => 'assigned',
            'subscription_code' => $subscription->subscription_code,
        ]);
    }

    public function test_bulk_delete_selected_subscriptions_queues_and_cleans_up_subscription_billing_and_ipam(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-BULK-0001',
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'issue_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->addDays(25)->toDateString(),
            'subtotal' => 99.99,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 99.99,
            'paid_amount' => 25,
            'balance_due' => 74.99,
            'status' => 'partially_paid',
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $subscription->customer_id,
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-BULK-0001',
            'amount' => 25,
            'payment_method' => 'cash',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        Queue::fake();

        $response = $this->actingAs($user)->postJson(route('subscriptions.bulk-destroy'), [
            'selection_mode' => 'selected',
            'ids' => [$subscription->id],
        ]);

        $response->assertAccepted();
        $response->assertJson([
            'message' => 'Subscription bulk delete queued. The cleanup will run in the background.',
        ]);

        Queue::assertPushed(BulkDeleteModelsJob::class);

        $run = BulkDeletionRun::withoutGlobalScopes()->firstOrFail();
        (new BulkDeleteModelsJob($run->id))->handle(app(BulkDeletionService::class));

        $this->assertSoftDeleted('subscriptions', [
            'id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'status' => 'suspended',
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_address' => '10.10.0.11',
            'status' => 'available',
            'subscription_code' => null,
        ]);

        $this->assertSoftDeleted('invoices', [
            'id' => $invoice->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertSoftDeleted('payments', [
            'id' => $payment->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('bulk_deletion_runs', [
            'id' => $run->id,
            'tenant_id' => $tenant->id,
            'module' => BulkDeletionRun::MODULE_SUBSCRIPTIONS,
            'action' => BulkDeletionRun::ACTION_DELETE,
            'status' => BulkDeletionRun::STATUS_COMPLETED,
            'processed_count' => 1,
            'deleted_count' => 1,
            'failed_count' => 0,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'subscription.bulk_delete_queued',
            'model_type' => BulkDeletionRun::class,
            'model_id' => $run->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'bulk_delete.completed',
            'model_type' => BulkDeletionRun::class,
            'model_id' => $run->id,
        ]);
    }

    public function test_destroy_deletes_a_subscription_without_failing_on_activity_logging(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();

        $response = $this->actingAs($user)->deleteJson(route('subscriptions.destroy', $subscription));

        $response->assertOk();
        $response->assertJson([
            'message' => 'Subscription deleted successfully.',
        ]);

        $this->assertSoftDeleted('subscriptions', [
            'id' => $subscription->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_address' => '10.10.0.11',
            'status' => 'available',
            'subscription_code' => null,
        ]);
    }

    public function test_update_can_remove_all_subscription_ip_routes_from_edit_form(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();

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
            'routeros_comment' => 'skybase:subscription-ip-route:remove',
            'routeros_sync_status' => 'synced',
        ]);

        $response = $this->actingAs($user)->put(route('subscriptions.update', $subscription), [
            'sync_ip_routes' => '1',
        ]);

        $response->assertRedirect(route('subscriptions.show', $subscription));

        $this->assertDatabaseMissing('subscription_ip_routes', [
            'id' => $ipRoute->id,
        ]);
        $this->assertDatabaseHas('ip_addresses', [
            'id' => $routeIp->id,
            'status' => 'available',
            'customer_id' => null,
            'subscription_code' => null,
        ]);
    }

    public function test_store_creates_system_managed_ip_routes_and_syncs_radius_replies(): void
    {
        [$tenant, $user, $customer, $plan, $router, $pool] = $this->createSubscriptionCreateDependencies();

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
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-IP-Address',
            'op' => ':=',
            'value' => '10.10.0.11',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '10.10.0.12/32 10.10.0.11 1',
        ]);
    }

    public function test_store_returns_validation_errors_for_missing_pppoe_credentials(): void
    {
        [$tenant, $user, $customer, $plan, $router] = $this->createSubscriptionCreateDependencies();

        $response = $this->actingAs($user)->postJson(route('subscriptions.store'), [
            'customer_id' => $customer->id,
            'name' => 'John Routed Service',
            'service_type' => 'pppoe',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
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

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pppoe_username', 'pppoe_password']);
        $response->assertJsonPath('errors.pppoe_username.0', 'PPP username is required for PPP connections.');
        $response->assertJsonPath('errors.pppoe_password.0', 'PPP password is required for PPP connections.');
    }

    public function test_update_primary_ip_resyncs_existing_framed_route_gateway(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();
        $subscription->forceFill([
            'connection_type' => 'pppoe',
            'pppoe_username' => 'john.routed',
            'pppoe_password' => 'secret-pass',
        ])->save();

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

        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-IP-Address',
            'op' => ':=',
            'value' => '10.10.0.12',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '10.10.0.13/32 10.10.0.12 1',
        ]);
        $this->assertSame(1, RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', 'john.routed')
            ->where('attribute', 'Framed-Route')
            ->count());
    }

    public function test_sync_ip_routes_action_rebuilds_radius_framed_routes(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();
        $subscription->forceFill([
            'connection_type' => 'pppoe',
            'pppoe_username' => 'john.routed',
            'pppoe_password' => 'secret-pass',
        ])->save();

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
            'routeros_comment' => 'skybase:subscription-ip-route:retry',
            'routeros_sync_status' => 'failed',
            'routeros_sync_error' => 'RADIUS route sync failed.',
        ]);
        $secondIpRoute = SubscriptionIpRoute::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'ip_pool_id' => $subscription->ip_pool_id,
            'ip_address' => '10.10.0.14',
            'cidr' => 32,
            'routeros_comment' => 'skybase:subscription-ip-route:retry-second',
            'routeros_sync_status' => 'failed',
            'routeros_sync_error' => 'RADIUS route sync failed.',
        ]);
        RadiusReply::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '192.0.2.0/24 10.10.0.11 1',
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.ip-routes.sync', $subscription));

        $response
            ->assertRedirect(route('subscriptions.show', $subscription))
            ->assertSessionHas('success', 'RADIUS IP route attributes synced successfully.');

        $ipRoute->refresh();
        $secondIpRoute->refresh();

        $this->assertSame('synced', $ipRoute->routeros_sync_status);
        $this->assertNull($ipRoute->routeros_sync_error);
        $this->assertSame('synced', $secondIpRoute->routeros_sync_status);
        $this->assertNull($secondIpRoute->routeros_sync_error);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-IP-Address',
            'op' => ':=',
            'value' => '10.10.0.11',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '10.10.0.13/32 10.10.0.11 1',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '10.10.0.14/32 10.10.0.11 1',
        ]);
        $this->assertDatabaseMissing('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-Route',
            'value' => '192.0.2.0/24 10.10.0.11 1',
        ]);
        $this->assertSame(2, RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', 'john.routed')
            ->where('attribute', 'Framed-Route')
            ->count());
    }

    public function test_sync_ip_routes_action_repairs_legacy_unique_radius_reply_index(): void
    {
        [$tenant, $user, $subscription] = $this->createSystemManagedSubscriptionWithPool();
        $subscription->forceFill([
            'connection_type' => 'pppoe',
            'pppoe_username' => 'john.routed',
            'pppoe_password' => 'secret-pass',
        ])->save();

        Schema::table('radreply', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'username', 'attribute']);
        });

        $routeIp = IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $subscription->ip_pool_id,
            'ip_address' => '10.10.0.13',
            'status' => 'assigned',
            'customer_id' => $subscription->customer_id,
            'metadata' => ['purpose' => 'subscription_ip_route'],
        ]);
        SubscriptionIpRoute::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'ip_pool_id' => $subscription->ip_pool_id,
            'ip_address_id' => $routeIp->id,
            'ip_address' => '10.10.0.13',
            'cidr' => 32,
            'routeros_comment' => 'skybase:subscription-ip-route:legacy-index',
            'routeros_sync_status' => 'failed',
            'routeros_sync_error' => 'RADIUS route sync failed.',
        ]);
        SubscriptionIpRoute::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'ip_pool_id' => $subscription->ip_pool_id,
            'ip_address' => '10.10.0.14',
            'cidr' => 32,
            'routeros_comment' => 'skybase:subscription-ip-route:legacy-index-second',
            'routeros_sync_status' => 'failed',
            'routeros_sync_error' => 'RADIUS route sync failed.',
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.ip-routes.sync', $subscription));

        $response
            ->assertRedirect(route('subscriptions.show', $subscription))
            ->assertSessionHas('success', 'RADIUS IP route attributes synced successfully.');

        $this->assertSame(2, RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', 'john.routed')
            ->where('attribute', 'Framed-Route')
            ->count());
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '10.10.0.13/32 10.10.0.11 1',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'john.routed',
            'attribute' => 'Framed-Route',
            'op' => '+=',
            'value' => '10.10.0.14/32 10.10.0.11 1',
        ]);
    }

    private function bindSuccessfulCredentialDisconnectService(object $tracker): void
    {
        $this->app->instance(SubscriptionSessionDisconnectService::class, new class($tracker) extends SubscriptionSessionDisconnectService
        {
            public function __construct(private object $tracker) {}

            public function disconnectForUsername(Subscription $subscription, string $username): SubscriptionSessionDisconnectResult
            {
                $this->tracker->calls[] = $username;

                return SubscriptionSessionDisconnectResult::success(
                    'Disconnected 1 active PPP session(s).',
                    'routeros-api',
                    $subscription->router?->id,
                    $subscription->router?->name,
                    1,
                );
            }

            public function recordActivity(Subscription $subscription, SubscriptionSessionDisconnectResult $result, ?User $causer = null): void {}
        });
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
     * @return array{0: Tenant, 1: User, 2: Subscription}
     */
    private function createRadiusAuthSubscription(): array
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-TEST-0005',
            'customer_type' => 'individual',
            'first_name' => 'Auth',
            'last_name' => 'Viewer',
            'name' => 'Auth Viewer',
            'email' => 'auth.viewer@example.com',
            'mobile' => '555-0105',
            'address_line1' => '321 Main Street',
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
            'name' => 'Fiber 50',
            'price' => 49.99,
            'billing_cycle' => 'monthly',
        ]);

        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Auth Router',
            'vendor' => 'Mikrotik',
            'enable_provisioning' => true,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-AUTH-0001',
            'name' => 'Auth Viewer Service',
            'service_type' => 'pppoe',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'ip_address' => '10.20.30.40',
            'pppoe_username' => 'auth.viewer',
            'pppoe_password' => 'secret-pass',
            'base_price' => 49.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 49.99,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now(),
            'activation_date' => now(),
            'next_billing_date' => now()->addMonth()->toDateString(),
        ]);

        return [$tenant, $user, $subscription];
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

    /**
     * @return array{0: Tenant, 1: User, 2: Subscription}
     */
    private function createPendingSystemManagedSubscriptionWithPool(): array
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-TEST-0004',
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Routed',
            'name' => 'Jane Routed',
            'email' => 'jane.routed@example.com',
            'mobile' => '555-0104',
            'address_line1' => '987 Main Street',
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
            'status' => 'available',
        ]);

        $pool->updateStatistics();

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-TEST-0004',
            'name' => 'Jane Routed Service',
            'service_type' => 'pppoe',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'site' => 'Head Office',
            'connection_type' => 'pppoe',
            'ip_address' => '10.10.0.11',
            'ip_pool_id' => $pool->id,
            'ip_management' => 'system',
            'pppoe_username' => 'jane.doe',
            'pppoe_password' => 'secret-pass',
            'base_price' => 99.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 99.99,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'grace_period_days' => 7,
            'status' => 'pending',
            'start_date' => now(),
            'activation_date' => null,
            'next_billing_date' => now()->addMonth()->toDateString(),
        ]);

        return [$tenant, $user, $subscription];
    }
}
