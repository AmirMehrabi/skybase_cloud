<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsLdapTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_tenant_ldap_settings(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('settings.update.ldap'), $this->validPayload());

        $response->assertRedirect(route('settings.index', ['tab' => 'ldap']));

        $connection = Setting::forTenant($tenant->id)->where('key', 'ldap.connection')->firstOrFail();
        $organizations = Setting::forTenant($tenant->id)->where('key', 'ldap.organization_sync')->firstOrFail();
        $customers = Setting::forTenant($tenant->id)->where('key', 'ldap.customer_sync')->firstOrFail();
        $subscriptions = Setting::forTenant($tenant->id)->where('key', 'ldap.subscription_sync')->firstOrFail();

        $this->assertSame('ldap', $connection->group);
        $this->assertTrue($connection->value['enabled']);
        $this->assertSame(['ldap1.alpha.test', 'ldap2.alpha.test'], $connection->value['hosts']);
        $this->assertSame('secret', $connection->value['password']);
        $this->assertSame(['ou=Skipped,dc=alpha,dc=test'], $organizations->value['excluded_ou_dns']);
        $this->assertSame('ou=customers,dc=alpha,dc=test', $customers->value['base_dn']);
        $this->assertSame('uid', $customers->value['map']['customer_code']);
        $this->assertSame('customerUid', $subscriptions->value['customer_attribute']);
    }

    public function test_blank_password_keeps_existing_ldap_password(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        Setting::create([
            'tenant_id' => $tenant->id,
            'key' => 'ldap.connection',
            'value' => ['password' => 'existing-secret'],
            'type' => 'json',
            'group' => 'ldap',
        ]);

        $payload = $this->validPayload();
        $payload['password'] = '';

        $this->actingAs($user)
            ->put(route('settings.update.ldap'), $payload)
            ->assertRedirect(route('settings.index', ['tab' => 'ldap']));

        $connection = Setting::forTenant($tenant->id)->where('key', 'ldap.connection')->firstOrFail();

        $this->assertSame('existing-secret', $connection->value['password']);
    }

    public function test_non_admin_cannot_update_ldap_settings(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'support',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->put(route('settings.update.ldap'), $this->validPayload())
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'enabled' => '1',
            'hosts' => "ldap1.alpha.test\nldap2.alpha.test",
            'port' => 389,
            'base_dn' => 'dc=alpha,dc=test',
            'username' => 'cn=readonly,dc=alpha,dc=test',
            'password' => 'secret',
            'timeout' => 5,
            'sync_interval_minutes' => 15,
            'missing_action' => 'mark_inactive',
            'organization_unique_attribute' => 'objectGUID',
            'organization_match_attribute' => 'objectGUID',
            'organization_map_code' => 'ou',
            'organization_map_name' => 'ou',
            'organization_map_description' => 'description',
            'customer_base_dn' => 'ou=customers,dc=alpha,dc=test',
            'customer_filter' => '(objectClass=inetOrgPerson)',
            'customer_unique_attribute' => 'uid',
            'customer_match_attribute' => 'uid',
            'customer_map_customer_code' => 'uid',
            'customer_map_name' => 'cn',
            'customer_map_email' => 'mail',
            'customer_map_phone' => 'telephoneNumber',
            'customer_map_mobile' => 'mobile',
            'organization_excluded_ou_dns' => ['ou=Skipped,dc=alpha,dc=test'],
            'subscription_base_dn' => 'ou=subscriptions,dc=alpha,dc=test',
            'subscription_filter' => '(objectClass=*)',
            'subscription_unique_attribute' => 'uid',
            'subscription_customer_attribute' => 'customerUid',
            'subscription_customer_match_field' => 'customer_code',
            'subscription_map_subscription_code' => 'uid',
            'subscription_map_pppoe_username' => 'uid',
            'subscription_map_pppoe_password' => 'userPassword',
            'subscription_map_ip_address' => 'framedIPAddress',
            'subscription_map_mac_address' => 'macAddress',
        ];
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
