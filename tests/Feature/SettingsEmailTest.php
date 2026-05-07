<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_tenant_email_settings(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('settings.update.email'), [
            'incoming_active' => '1',
            'incoming_protocol' => 'imap',
            'incoming_host' => 'imap.alpha.test',
            'incoming_port' => 993,
            'incoming_encryption' => 'ssl',
            'incoming_username' => 'support@alpha.test',
            'incoming_password' => 'incoming-secret',
            'incoming_mailbox' => 'INBOX',
            'outgoing_active' => '1',
            'outgoing_host' => 'smtp.alpha.test',
            'outgoing_port' => 587,
            'outgoing_encryption' => 'tls',
            'outgoing_username' => 'mailer@alpha.test',
            'outgoing_password' => 'outgoing-secret',
            'outgoing_from_email' => 'billing@alpha.test',
            'outgoing_from_name' => 'Alpha Billing',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'email']));

        $incoming = Setting::forTenant($tenant->id)->where('key', 'email.incoming')->firstOrFail();
        $outgoing = Setting::forTenant($tenant->id)->where('key', 'email.outgoing')->firstOrFail();

        $this->assertSame('email', $incoming->group);
        $this->assertSame('imap.alpha.test', $incoming->value['host']);
        $this->assertTrue($incoming->value['active']);
        $this->assertSame('smtp.alpha.test', $outgoing->value['host']);
        $this->assertSame('billing@alpha.test', $outgoing->value['from_email']);
        $this->assertTrue($outgoing->value['active']);
    }

    public function test_blank_password_fields_keep_existing_email_passwords(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        Setting::create([
            'tenant_id' => $tenant->id,
            'key' => 'email.incoming',
            'value' => ['password' => 'existing-incoming'],
            'type' => 'json',
            'group' => 'email',
        ]);
        Setting::create([
            'tenant_id' => $tenant->id,
            'key' => 'email.outgoing',
            'value' => ['password' => 'existing-outgoing'],
            'type' => 'json',
            'group' => 'email',
        ]);

        $this->actingAs($user)->put(route('settings.update.email'), [
            'incoming_protocol' => 'imap',
            'incoming_host' => 'imap.alpha.test',
            'incoming_port' => 993,
            'incoming_encryption' => 'ssl',
            'incoming_username' => 'support@alpha.test',
            'incoming_mailbox' => 'INBOX',
            'outgoing_host' => 'smtp.alpha.test',
            'outgoing_port' => 587,
            'outgoing_encryption' => 'tls',
            'outgoing_username' => 'mailer@alpha.test',
            'outgoing_from_email' => 'billing@alpha.test',
            'outgoing_from_name' => 'Alpha Billing',
        ])->assertRedirect(route('settings.index', ['tab' => 'email']));

        $incoming = Setting::forTenant($tenant->id)->where('key', 'email.incoming')->firstOrFail();
        $outgoing = Setting::forTenant($tenant->id)->where('key', 'email.outgoing')->firstOrFail();

        $this->assertSame('existing-incoming', $incoming->value['password']);
        $this->assertSame('existing-outgoing', $outgoing->value['password']);
    }

    public function test_non_admin_cannot_update_email_settings(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'support',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('settings.update.email'), [
            'incoming_protocol' => 'imap',
            'incoming_encryption' => 'ssl',
            'outgoing_encryption' => 'tls',
        ]);

        $response->assertForbidden();
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
