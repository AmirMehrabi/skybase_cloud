<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\NotificationPreference;
use App\Models\Tenant;
use App\Models\TenantNotification;
use App\Models\User;
use App\Notifications\TenantDatabaseNotification;
use App\Services\TenantNotificationService;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_mark_own_notifications_as_read(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);

        $notification = $this->createNotification($tenant, $admin, ['title' => 'Router offline']);

        $this->actingAs($admin)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Router offline');

        $this->actingAs($admin)
            ->patch(route('notifications.read', ['notification' => $notification->id ?: $notification->getKey()]))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_admin_cannot_read_another_tenant_notification(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $otherTenant = $this->createTenant('beta-net');
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $otherAdmin = User::factory()->create(['tenant_id' => $otherTenant->id, 'role' => 'admin', 'status' => 'active']);
        $notification = $this->createNotification($otherTenant, $otherAdmin);

        $this->actingAs($admin)
            ->patch(route('notifications.read', ['notification' => $notification->id]))
            ->assertForbidden();
    }

    public function test_customer_can_only_view_own_notifications(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant, ['email' => 'jane@example.com']);
        $otherCustomer = $this->createCustomer($tenant, ['email' => 'john@example.com']);

        $this->createNotification($tenant, $customer, ['title' => 'Your invoice is ready']);
        $this->createNotification($tenant, $otherCustomer, ['title' => 'Hidden notification']);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.notifications.index'))
            ->assertOk()
            ->assertSee('Your invoice is ready')
            ->assertDontSee('Hidden notification');
    }

    public function test_optional_notifications_respect_recipient_toggle(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);

        NotificationPreference::query()->create([
            'tenant_id' => $tenant->id,
            'notifiable_type' => $admin->getMorphClass(),
            'notifiable_id' => $admin->id,
            'notifications_enabled' => false,
            'in_app_enabled' => false,
            'email_enabled' => false,
            'sms_enabled' => false,
        ]);

        app(TenantNotificationService::class)->notifyAdmins($tenant->id, NotificationEventRegistry::TICKET_CREATED, [
            'title' => 'New ticket',
        ]);

        $this->assertSame(0, TenantNotification::query()->count());
    }

    public function test_critical_admin_notifications_bypass_recipient_opt_out(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);

        NotificationPreference::query()->create([
            'tenant_id' => $tenant->id,
            'notifiable_type' => $admin->getMorphClass(),
            'notifiable_id' => $admin->id,
            'notifications_enabled' => false,
            'in_app_enabled' => false,
            'email_enabled' => false,
            'sms_enabled' => false,
        ]);

        app(TenantNotificationService::class)->notifyAdmins($tenant->id, NotificationEventRegistry::OPERATIONAL_FAILURE, [
            'title' => 'Router disconnect failed',
        ]);

        $this->assertSame(1, TenantNotification::query()->count());
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCustomer(Tenant $tenant, array $overrides = []): Customer
    {
        return Customer::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $tenant->id,
            'customer_code' => Customer::generateCustomerCode(),
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
            'password' => 'password123',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createNotification(Tenant $tenant, User|Customer $recipient, array $data = []): TenantNotification
    {
        return TenantNotification::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'type' => TenantDatabaseNotification::class,
            'notifiable_type' => $recipient->getMorphClass(),
            'notifiable_id' => $recipient->id,
            'data' => array_merge([
                'event_key' => NotificationEventRegistry::OPERATIONAL_FAILURE,
                'title' => 'Operational failure',
                'body' => 'An operational failure occurred.',
                'category' => 'system',
                'severity' => 'critical',
                'action_url' => null,
            ], $data),
        ]);
    }
}
