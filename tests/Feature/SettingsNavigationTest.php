<?php

namespace Tests\Feature;

use App\Models\CommunicationTemplate;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsNavigationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['id' => (string) Str::uuid(), 'name' => 'Northstar Networks', 'slug' => 'northstar-networks', 'company_name' => 'Northstar Networks', 'email' => 'team@example.test', 'timezone' => 'UTC', 'status' => 'active']);
        $this->actingAs(User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Alex Morgan', 'role' => 'admin', 'status' => 'active']));
        app()->instance('current_tenant', $this->tenant);
    }

    public function test_settings_opens_a_card_directory_without_rendering_configuration_forms(): void
    {
        $response = $this->get(route('settings.index'));
        $response->assertOk()->assertSee('Company &amp; localization', false)->assertSee('Email connections')->assertSee('SMS gateway')->assertSee('Roles &amp; permissions', false)
            ->assertSee(route('settings.section', ['section' => 'general']), false)->assertSee('aria-label="Breadcrumb"', false)->assertDontSee('showTab(')->assertDontSee('name="incoming_password"', false);

    }

    public function test_each_setting_section_renders_only_its_form_with_breadcrumbs(): void
    {
        foreach (['general', 'branding', 'billing', 'email', 'notifications', 'ldap'] as $section) {
            $response = $this->get(route('settings.section', ['section' => $section]));
            $response->assertOk()->assertSee('aria-label="Breadcrumb"', false)->assertSee('aria-label="Settings sections"', false)->assertSee('All settings');
            if ($section !== 'email') {
                $response->assertDontSee('name="incoming_password"', false);
            }

        }
        $this->get(route('settings.sms'))->assertOk()->assertSee('All settings')->assertSee('aria-label="Settings sections"', false);
    }

    public function test_old_tab_links_redirect_and_unknown_sections_are_rejected(): void
    {
        $this->get(route('settings.index', ['tab' => 'email']))->assertRedirect(route('settings.section', ['section' => 'email']));
        $this->get('/settings/not-a-section')->assertNotFound();
        $this->get(route('settings.index', ['tab' => '../general']))->assertNotFound();
    }

    public function test_sidebar_has_contextual_campaign_and_notification_submenus(): void
    {
        $response = $this->get(route('campaigns.index'));
        $response->assertOk()->assertSee('sidebar-campaigns')->assertSee('sidebar-notifications')->assertSee('All campaigns')->assertSee('Inbox')->assertSee(route('campaigns.templates'), false)->assertSee(route('campaigns.deliveries'), false);

    }

    public function test_campaign_templates_and_delivery_activity_have_distinct_contexts(): void
    {
        CommunicationTemplate::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Maintenance announcement', 'event_key' => null]);
        CommunicationTemplate::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Invoice notification', 'event_key' => 'billing.invoice_created']);
        $this->get(route('campaigns.templates'))->assertOk()->assertSee('Maintenance announcement')->assertDontSee('Invoice notification');
        $this->get(route('communications.index'))->assertOk()->assertSee('Invoice notification')->assertDontSee('Maintenance announcement');
        $this->get(route('campaigns.deliveries'))->assertOk()->assertSee('Follow campaign delivery results');
        $this->get(route('communications.deliveries'))->assertOk()->assertSee('Track automatic notifications');
    }

    public function test_settings_cards_and_submenus_respect_role_permissions(): void
    {
        Role::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'settings_reader', 'permissions' => ['settings.read'], 'is_system' => false]);
        $this->actingAs(User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'settings_reader', 'status' => 'active']));
        $this->get(route('settings.index'))->assertOk()->assertDontSee('Team members')->assertDontSee('Roles &amp; permissions', false)->assertDontSee('sidebar-campaigns')->assertDontSee('sidebar-notifications');
        $this->get(route('settings.section', ['section' => 'email']))->assertOk();
        $this->get(route('communications.index'))->assertForbidden();
    }

    public function test_team_and_access_settings_have_breadcrumbs(): void
    {
        foreach (['users', 'roles', 'user-groups'] as $section) {
            $this->get(route('admin.tenant.'.$section.'.index'))->assertOk()->assertSee('aria-label="Breadcrumb"', false)->assertSee(route('settings.index'), false);
        }
    }
}
