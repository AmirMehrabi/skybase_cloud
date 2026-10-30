<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Router;
use App\Models\SkybaseAccount;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\TenantOperationalOverview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function central(): User
    {
        auth()->logout();
        $user = User::factory()->create(['tenant_id' => null, 'role' => 'super_admin', 'mfa_confirmed_at' => now()]);
        $this->actingAs($user)->withSession(['mfa_verified_user' => $user->id, 'mfa_verified_at' => now()->timestamp]);

        return $user;
    }

    public function test_console_requires_central_identity_mfa_and_explicit_enablement(): void
    {
        config(['skybase_billing.owner_console_enabled' => true]);
        $tenant = Tenant::factory()->create();
        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']));
        $this->get(route('admin.super-admin.tenants.index'))->assertForbidden();
        auth()->logout();
        $user = User::factory()->create(['tenant_id' => null, 'role' => 'super_admin']);
        $this->actingAs($user)->get(route('admin.super-admin.tenants.index'))->assertRedirect(route('mfa.setup'));
        $this->central();
        config(['skybase_billing.owner_console_enabled' => false]);
        $this->get(route('admin.super-admin.tenants.index'))->assertNotFound();
    }

    public function test_console_actions_are_audited_and_archive_preserves_data(): void
    {
        config(['skybase_billing.owner_console_enabled' => true]);
        $tenant = Tenant::factory()->create();
        $this->central();
        $this->get(route('admin.super-admin.tenants.index'))->assertOk()->assertSee($tenant->company_name);
        $this->get(route('admin.super-admin.tenants.show', $tenant))->assertOk();
        foreach (['package' => ['package_key' => 'starter'], 'trial' => ['trial_ends_at' => now()->addDays(10)->toDateString()], 'limit' => ['subscriber_limit' => 250], 'note' => [], 'suspend' => [], 'activate' => [], 'archive' => []] as $action => $extra) {
            $this->put(route('admin.super-admin.tenants.update', $tenant), $extra + ['action' => $action, 'reason' => 'Account review'])->assertRedirect();
            $this->assertDatabaseHas('tenant_admin_events', ['tenant_id' => $tenant->id, 'event' => 'owner.'.$action]);
        }
        $this->assertNotNull($tenant->fresh()->archived_at);
        $this->assertDatabaseHas('tenant_notes', ['tenant_id' => $tenant->id, 'body' => 'Account review']);
        $this->assertNull(app(TenantContext::class)->id());
    }

    public function test_backfill_is_repeatable_and_does_not_infer_terms(): void
    {
        config(['skybase_billing.enabled' => true]);
        $tenant = Tenant::factory()->create();
        $this->artisan('skybase:backfill-accounts')->assertSuccessful();
        $this->artisan('skybase:backfill-accounts')->assertSuccessful();
        $this->assertDatabaseCount('skybase_accounts', 1);
        $this->assertDatabaseHas('skybase_accounts', ['tenant_id' => $tenant->id, 'status' => 'unconfigured', 'package_key' => null]);
        $this->assertSame(0, SkybaseAccount::query()->count());
    }

    public function test_suspension_blocks_web_access_without_changing_network_tenant_status(): void
    {
        config(['skybase_billing.owner_console_enabled' => true]);
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $this->central();
        $this->put(route('admin.super-admin.tenants.update', $tenant), ['action' => 'suspend', 'reason' => 'Access review'])->assertRedirect();
        $this->assertSame('active', $tenant->fresh()->status);
        $this->assertTrue($tenant->fresh()->application_access_suspended);
        $this->actingAs($owner)->get(route('dashboard'))->assertForbidden();
    }

    public function test_reason_is_required_and_commercial_filters_work(): void
    {
        config(['skybase_billing.owner_console_enabled' => true]);
        $tenant = Tenant::factory()->create();
        $this->central();
        $this->put(route('admin.super-admin.tenants.update', $tenant), ['action' => 'archive'])->assertSessionHasErrors('reason');
        $this->assertNull($tenant->fresh()->archived_at);
        $this->get(route('admin.super-admin.tenants.index', ['commercial' => 'unconfigured', 'activity' => 'inactive']))->assertOk()->assertSee($tenant->company_name);
        $this->assertNull(app(TenantContext::class)->id());
    }

    public function test_delete_requires_confirmation_and_retains_audited_records(): void
    {
        config(['skybase_billing.owner_console_enabled' => true]);
        $tenant = Tenant::factory()->create();
        $other = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $this->central();
        $this->delete(route('admin.super-admin.tenants.destroy', $tenant))->assertSessionHasErrors('confirmed');
        $this->assertNotNull(Tenant::query()->find($tenant->id));
        $this->delete(route('admin.super-admin.tenants.destroy', $tenant), ['confirmed' => '1'])->assertRedirect(route('admin.super-admin.tenants.index'));
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
        $this->assertTrue(Tenant::withTrashed()->findOrFail($tenant->id)->application_access_suspended);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('tenant_admin_events', ['tenant_id' => $tenant->id, 'event' => 'owner.deleted']);
        $this->assertNotNull(Tenant::query()->find($other->id));
        $this->get(route('admin.super-admin.tenants.show', $tenant))->assertNotFound();
        $this->actingAs($owner)->get(route('dashboard'))->assertForbidden();
    }

    public function test_tenant_users_cannot_delete_tenants(): void
    {
        config(['skybase_billing.owner_console_enabled' => true]);
        $tenant = Tenant::factory()->create();
        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']));
        $this->delete(route('admin.super-admin.tenants.destroy', $tenant), ['confirmed' => '1'])->assertForbidden();
        $this->assertNotNull(Tenant::query()->find($tenant->id));
    }

    public function test_operational_overview_counts_are_scoped_and_visible_on_both_pages(): void
    {
        config(['skybase_billing.owner_console_enabled' => true]);
        $tenant = Tenant::factory()->create();
        $other = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $otherCustomer = Customer::factory()->create(['tenant_id' => $other->id]);
        foreach ([[$tenant, $customer, 'Visible circuit'], [$other, $otherCustomer, 'Other ISP circuit']] as [$ownerTenant, $subscriber, $name]) {
            Subscription::query()->create(['tenant_id' => $ownerTenant->id, 'customer_id' => $subscriber->id, 'name' => $name, 'service_type' => 'hotspot', 'status' => 'pending']);
        }
        Router::factory()->create(['tenant_id' => $tenant->id, 'status' => 'online']);
        Router::factory()->create(['tenant_id' => $other->id, 'status' => 'offline']);
        $this->central();
        $overview = app(TenantContext::class)->run($tenant->id, fn () => app(TenantOperationalOverview::class)->forTenant($tenant, true));
        $this->assertSame(1, $overview['customers']);
        $this->assertSame(1, $overview['subscriptions']);
        $this->assertSame(1, $overview['online_routers']);
        $this->assertSame(['pending' => 1], $overview['subscription_statuses']);
        $this->get(route('admin.super-admin.tenants.index'))->assertOk()->assertSee('Subscriptions')->assertSee('Delete tenant');
        $this->get(route('admin.super-admin.tenants.show', $tenant))->assertOk()->assertSee('Operational overview')->assertSee('Visible circuit')->assertDontSee('Other ISP circuit')->assertSee('Delete tenant');
        $this->assertNull(app(TenantContext::class)->id());
    }
}
