<?php

namespace Tests\Feature;

use App\Jobs\Subscriptions\SuspendSubscriptionJob;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\RadiusCheck;
use App\Models\RadiusReply;
use App\Models\RadiusUserGroup;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RadiusProvisioningService;
use App\Services\RouterOs\RouterOsClient;
use App\Services\SubscriptionSessionDisconnectService;
use App\Services\TenantNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionSuspensionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_subscription_rejects_future_radius_authentication(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription();

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'alpha.user',
            'attribute' => 'Cleartext-Password',
        ]);

        $subscription->suspend();

        $this->assertSame(0, RadiusCheck::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('username', 'alpha.user')->where('attribute', 'Cleartext-Password')->count());
        $this->assertSame(0, RadiusReply::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('username', 'alpha.user')->count());
        $this->assertSame(0, RadiusUserGroup::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('username', 'alpha.user')->count());

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'alpha.user',
            'attribute' => 'Auth-Type',
            'op' => ':=',
            'value' => 'Reject',
        ]);
    }

    public function test_reactivated_subscription_removes_radius_reject_rule(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription();

        $subscription->suspend();
        $subscription->activate();

        $this->assertSame(0, RadiusCheck::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('username', 'alpha.user')->where('attribute', 'Auth-Type')->where('value', 'Reject')->count());

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'alpha.user',
            'attribute' => 'Cleartext-Password',
            'value' => 'secret-pass',
        ]);
    }

    public function test_suspend_logs_activity_when_router_disconnect_is_skipped(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription([
            'api_username' => null,
            'api_password' => null,
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        Queue::fake();

        $response = $this->actingAs($user)->post(route('subscriptions.suspend', $subscription));

        $response->assertRedirect(route('subscriptions.show', $subscription));

        Queue::assertPushedOn('subscriptions', SuspendSubscriptionJob::class);

        Queue::assertPushed(SuspendSubscriptionJob::class, function (SuspendSubscriptionJob $job) use ($subscription, $tenant, $user): bool {
            return $job->subscriptionId === $subscription->id
                && $job->tenantId === $tenant->id
                && $job->causedByUserId === $user->id;
        });

        (new SuspendSubscriptionJob($subscription->id, $tenant->id, $user->id))->handle(
            app(RadiusProvisioningService::class),
            app(SubscriptionSessionDisconnectService::class),
            app(TenantNotificationService::class),
        );

        $this->assertDatabaseHas('activity_log', [
            'tenant_id' => $tenant->id,
            'subject_type' => Subscription::class,
            'subject_id' => $subscription->id,
            'event' => 'session_disconnect_skipped',
        ]);

        $activity = Activity::query()->where('event', 'session_disconnect_skipped')->latest()->firstOrFail();

        $this->assertSame('RouterOS API credentials are missing.', $activity->properties->get('message'));
    }

    public function test_command_kicks_suspended_online_subscription_with_routeros_api(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription();
        $subscription->suspend();
        $subscription->forceFill([
            'connection_status' => 'online',
            'connection_status_checked_at' => now()->subMinutes(5),
        ])->saveQuietly();

        $this->app->instance(RouterOsClient::class, new class extends RouterOsClient
        {
            public function execute(Router $router, callable $callback): mixed
            {
                return 1;
            }
        });

        $this->artisan('subscriptions:kick-suspended-online')
            ->expectsOutputToContain('Checked: 1, kicked: 1, skipped: 0, failed: 0')
            ->assertExitCode(0);

        $subscription->refresh();

        $this->assertSame('offline', $subscription->connection_status);
        $this->assertDatabaseHas('activity_log', [
            'tenant_id' => $tenant->id,
            'subject_type' => Subscription::class,
            'subject_id' => $subscription->id,
            'event' => 'session_disconnect_succeeded',
        ]);
    }

    public function test_command_reports_suspended_online_subscriptions_that_cannot_be_kicked(): void
    {
        [$tenant, $subscription] = $this->createPppoeSubscription([
            'api_username' => null,
            'api_password' => null,
        ]);
        $subscription->suspend();
        $subscription->forceFill([
            'connection_status' => 'online',
            'connection_status_checked_at' => now()->subMinutes(5),
        ])->saveQuietly();

        $this->artisan('subscriptions:kick-suspended-online')
            ->expectsOutputToContain('Checked: 1, kicked: 0, skipped: 1, failed: 0')
            ->assertExitCode(1);

        $this->assertDatabaseHas('activity_log', [
            'tenant_id' => $tenant->id,
            'subject_type' => Subscription::class,
            'subject_id' => $subscription->id,
            'event' => 'session_disconnect_skipped',
        ]);
    }

    /**
     * @param  array<string, mixed>  $routerOverrides
     * @return array{0: Tenant, 1: Subscription}
     */
    private function createPppoeSubscription(array $routerOverrides = []): array
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
            ...$routerOverrides,
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
}
