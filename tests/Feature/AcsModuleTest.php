<?php

namespace Tests\Feature;

use App\Jobs\RunAcsAction;
use App\Models\AcsConnection;
use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\DeviceAction;
use App\Models\ProvisioningProfile;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\AcsClient;
use App\Services\AcsProvisioningService;
use App\Services\AcsService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AcsModuleTest extends TestCase
{
    use RefreshDatabase;

    private function owner(bool $enabled = true): User
    {
        $tenant = Tenant::factory()->create(['acs_enabled' => $enabled]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);
        $this->actingAs($user);

        return $user;
    }

    private function device(User $user): CustomerDevice
    {
        return app(TenantContext::class)->run($user->tenant_id, function () use ($user): CustomerDevice {
            $connection = AcsConnection::factory()->create(['tenant_id' => $user->tenant_id]);

            return CustomerDevice::factory()->create(['tenant_id' => $user->tenant_id, 'acs_connection_id' => $connection->id]);
        });
    }

    public function test_disabled_tenants_have_no_acs_navigation_or_settings(): void
    {
        $this->owner(false);
        $this->get(route('settings.index'))->assertOk()->assertDontSee('ACS integration')->assertDontSee('Customer Devices');
        $this->get(route('acs.index'))->assertForbidden();
        $this->post(route('acs.sync'))->assertForbidden();
    }

    public function test_enabled_owner_gets_setup_journey(): void
    {
        $this->owner();
        $this->get(route('acs.index'))->assertOk()->assertSee('Connect your first ACS');
        $this->get(route('acs.settings'))->assertOk()->assertSee('MikroTik hAP lite')->assertSee('Automatic provisioning');
    }

    public function test_connection_requires_approved_endpoint_and_preserves_secret(): void
    {
        $user = $this->owner();
        $device = $this->device($user);
        $device->connection->update(['password' => 'saved-secret']);
        $this->put(route('acs.save'), ['name' => 'CPE ACS', 'base_url' => 'http://169.254.169.254', 'automation_enabled' => false])->assertSessionHasErrors('base_url');
        $this->put(route('acs.save'), ['name' => 'CPE ACS', 'base_url' => 'http://127.0.0.1:7557', 'password' => '', 'automation_enabled' => false])->assertSessionHasNoErrors();
        $this->assertSame('saved-secret', $device->connection->fresh()->password);
        $this->assertStringNotContainsString('saved-secret', $device->connection->getRawOriginal('password'));
    }

    public function test_live_connection_test_and_inventory_sync_use_genieacs_api(): void
    {
        $user = $this->owner();
        $device = $this->device($user);
        Http::fake(['*/devices/*' => Http::response([['_id' => $device->external_id, '_deviceId' => ['_SerialNumber' => 'TEST123', '_Manufacturer' => 'MikroTik', '_ProductClass' => 'hAP lite'], '_lastInform' => now()->toISOString(), 'Device' => ['DeviceInfo' => ['SoftwareVersion' => ['_value' => '7.20']], 'WiFi' => ['SSID' => ['1' => ['SSID' => ['_writable' => true, '_type' => 'xsd:string', '_value' => 'Test']]]]]]])]);
        $this->post(route('acs.test'))->assertSessionHas('success');
        app(TenantContext::class)->run($user->tenant_id, fn () => app(AcsService::class)->sync($device->connection));
        $this->assertSame('7.20', $device->fresh()->firmware);
        $this->assertArrayHasKey('Device.WiFi.SSID.1.SSID', $device->fresh()->capabilities);
    }

    public function test_cross_tenant_device_cannot_be_read_or_changed(): void
    {
        $other = $this->owner();
        $device = $this->device($other);
        $this->owner();
        $this->get(route('acs.show', $device))->assertNotFound();
        $this->post(route('acs.action', $device), ['operation' => 'refresh'])->assertNotFound();
    }

    public function test_support_without_acs_permission_is_denied(): void
    {
        $owner = $this->owner();
        $user = User::factory()->create(['tenant_id' => $owner->tenant_id, 'role' => 'support', 'status' => 'active']);
        $this->actingAs($user)->get(route('acs.index'))->assertForbidden();
    }

    public function test_device_page_renders_and_assignment_requires_verified_ownership(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        $this->get(route('acs.show', $device))->assertOk()->assertSee('Customer assignment')->assertSee('Reboot equipment');
        $this->put(route('acs.assign', $device), [])->assertSessionHasErrors('ownership_verified');
        $this->put(route('acs.assign', $device), ['ownership_verified' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('device_actions', ['customer_device_id' => $device->id, 'operation' => 'assignment', 'status' => 'completed']);
    }

    public function test_reboot_requires_confirmation_and_duplicate_commands_are_blocked(): void
    {
        Queue::fake();
        $owner = $this->owner();
        $device = $this->device($owner);
        $this->post(route('acs.action', $device), ['operation' => 'reboot'])->assertSessionHasErrors('confirmed');
        $this->post(route('acs.action', $device), ['operation' => 'reboot', 'confirmed' => 1])->assertSessionHasNoErrors();
        $this->post(route('acs.action', $device), ['operation' => 'refresh'])->assertSessionHasErrors('operation');
        Queue::assertPushed(RunAcsAction::class, 1);
    }

    public function test_wifi_rejects_undiscovered_or_arbitrary_parameter_paths(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        $this->post(route('acs.action', $device), ['operation' => 'wifi', 'ssid_path' => 'Device.ManagementServer.URL', 'ssid' => 'Bad'])->assertSessionHasErrors('ssid_path');
    }

    public function test_queued_job_does_not_contact_acs_after_disabling(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        $action = DeviceAction::factory()->create(['tenant_id' => $owner->tenant_id, 'customer_device_id' => $device->id, 'user_id' => $owner->id]);
        Tenant::query()->find($owner->tenant_id)->update(['acs_enabled' => false]);
        Http::fake();
        (new RunAcsAction($owner->tenant_id, $action->id))->handle(app(AcsClient::class));
        Http::assertNothingSent();
        $this->assertSame('cancelled', $action->fresh()->status);
    }

    public function test_accepted_command_is_pending_not_completed(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        $action = DeviceAction::factory()->create(['tenant_id' => $owner->tenant_id, 'customer_device_id' => $device->id, 'user_id' => $owner->id]);
        Http::fake(['*' => Http::response(['_id' => 'test-task'], 202)]);
        (new RunAcsAction($owner->tenant_id, $action->id))->handle(app(AcsClient::class));
        $this->assertSame('pending', $action->fresh()->status);
        Http::assertSent(fn ($request): bool => ! str_contains($request->url(), 'connection_request'));
        $this->assertNull($action->fresh()->payload);
    }

    public function test_timeout_does_not_retry_a_potentially_accepted_command(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        $action = DeviceAction::factory()->create(['tenant_id' => $owner->tenant_id, 'customer_device_id' => $device->id, 'user_id' => $owner->id]);
        Http::fake(['*' => Http::failedConnection()]);
        $job = new RunAcsAction($owner->tenant_id, $action->id);
        $job->handle(app(AcsClient::class));
        $job->handle(app(AcsClient::class));
        $this->assertSame('uncertain', $action->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_profile_requires_discovered_writable_parameters(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        $this->post(route('acs.profile', $device), ['name' => 'Wrong', 'parameters' => [['path' => 'Device.ManagementServer.URL', 'value' => 'bad']]])->assertSessionHasErrors('parameters');
        $device->update(['capabilities' => ['Device.WiFi.SSID.1.SSID' => 'xsd:string']]);
        $this->post(route('acs.profile', $device), ['name' => 'Wi-Fi', 'parameters' => [['path' => 'Device.WiFi.SSID.1.SSID', 'value' => 'SkyBase']]])->assertSessionHasNoErrors();
        $this->assertNotNull($device->fresh()->provisioning_profile_id);
    }

    public function test_device_assignment_rejects_other_tenants_customer(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        $other = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $other->id]);
        $this->put(route('acs.assign', $device), ['customer_id' => $customer->id, 'ownership_verified' => 1])->assertNotFound();
    }

    public function test_manual_provisioning_uses_assigned_subscription_and_encrypted_profile(): void
    {
        Queue::fake();
        $owner = $this->owner();
        $device = $this->device($owner);
        app(TenantContext::class)->run($owner->tenant_id, function () use ($owner, $device): void {
            $customer = Customer::factory()->create(['tenant_id' => $owner->tenant_id]);
            $subscription = Subscription::factory()->create(['tenant_id' => $owner->tenant_id, 'customer_id' => $customer->id]);
            $profile = ProvisioningProfile::factory()->create(['tenant_id' => $owner->tenant_id]);
            $device->update(['customer_id' => $customer->id, 'subscription_id' => $subscription->id, 'provisioning_profile_id' => $profile->id, 'capabilities' => ['Device.WiFi.SSID.1.SSID' => 'xsd:string']]);
            $action = app(AcsProvisioningService::class)->queue($device, $owner->id);
            $this->assertNotNull($action);
            $this->assertSame('SkyBase', $action->payload['parameterValues'][0][1]);
            $this->assertStringNotContainsString('SkyBase', $profile->getRawOriginal('parameters'));
        });
        Queue::assertPushed(RunAcsAction::class);
    }

    public function test_automatic_provisioning_is_optional_and_rechecks_toggle_at_execution(): void
    {
        Queue::fake();
        $owner = $this->owner();
        $device = $this->device($owner);
        app(TenantContext::class)->run($owner->tenant_id, function () use ($owner, $device): void {
            $customer = Customer::factory()->create(['tenant_id' => $owner->tenant_id]);
            $subscription = Subscription::factory()->create(['tenant_id' => $owner->tenant_id, 'customer_id' => $customer->id]);
            $profile = ProvisioningProfile::factory()->create(['tenant_id' => $owner->tenant_id]);
            $device->update(['customer_id' => $customer->id, 'subscription_id' => $subscription->id, 'provisioning_profile_id' => $profile->id, 'capabilities' => ['Device.WiFi.SSID.1.SSID' => 'xsd:string']]);
            $service = app(AcsProvisioningService::class);
            $this->assertNull($service->queue($device, null, true));
            $device->connection->update(['automation_enabled' => true]);
            $action = $service->queue($device, null, true);
            $this->assertNotNull($action);
            $device->connection->update(['automation_enabled' => false]);
            Http::fake();
            (new RunAcsAction($owner->tenant_id, $action->id))->handle(app(AcsClient::class));
            Http::assertNothingSent();
            $this->assertSame('cancelled', $action->fresh()->status);
        });
    }

    public function test_pending_task_fault_is_reported_as_failure(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        $action = DeviceAction::factory()->create(['tenant_id' => $owner->tenant_id, 'customer_device_id' => $device->id, 'user_id' => $owner->id, 'status' => 'pending', 'external_task_id' => 'task-1', 'submitted_at' => now()]);
        Http::fake(['*/devices/*' => Http::response([]), '*/faults/*' => Http::response([['device' => $device->external_id, 'channel' => 'task_task-1']])]);
        app(TenantContext::class)->run($owner->tenant_id, fn () => app(AcsService::class)->sync($device->connection));
        $this->assertSame('failed', $action->fresh()->status);
    }

    public function test_customer_assignment_must_match_subscription_owner(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        $customers = Customer::factory()->count(2)->create(['tenant_id' => $owner->tenant_id]);
        $subscription = Subscription::factory()->create(['tenant_id' => $owner->tenant_id, 'customer_id' => $customers[0]->id]);
        $this->put(route('acs.assign', $device), ['customer_id' => $customers[1]->id, 'subscription_id' => $subscription->id, 'ownership_verified' => 1])->assertSessionHasErrors('subscription_id');
    }

    public function test_activity_response_excludes_command_payloads(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        DeviceAction::factory()->create(['tenant_id' => $owner->tenant_id, 'customer_device_id' => $device->id, 'payload' => ['secret' => 'never-display']]);
        $this->getJson(route('acs.activity', $device))->assertOk()->assertJsonMissingPath('actions.0.payload')->assertDontSee('never-display');
    }

    public function test_disabled_tenant_does_not_see_acs_role_permissions(): void
    {
        $this->owner(false);
        $this->get(route('admin.tenant.roles.create'))->assertOk()->assertDontSee('Customer Devices');
    }

    public function test_staff_inventory_respects_customer_user_groups(): void
    {
        $owner = $this->owner();
        $device = $this->device($owner);
        $groups = UserGroup::factory()->count(2)->create(['tenant_id' => $owner->tenant_id]);
        $customers = collect();
        foreach ($groups as $group) {
            $customers->push(Customer::factory()->create(['tenant_id' => $owner->tenant_id, 'user_group_id' => $group->id]));
        }
        $device->update(['customer_id' => $customers[0]->id]);
        $other = CustomerDevice::factory()->create(['tenant_id' => $owner->tenant_id, 'acs_connection_id' => $device->acs_connection_id, 'customer_id' => $customers[1]->id]);
        $staff = User::factory()->create(['tenant_id' => $owner->tenant_id, 'role' => 'support', 'status' => 'active', 'user_group_id' => $groups[0]->id]);
        $this->actingAs($staff);
        $ids = app(TenantContext::class)->run($owner->tenant_id, fn () => app(AcsService::class)->visibleDevices()->pluck('id')->all());
        $this->assertSame([$device->id], $ids);
        $this->assertNotContains($other->id, $ids);
    }
}
