<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Tests\TestCase;

class CloudAccessModeTest extends TestCase
{
    public function test_homepage_is_visible_when_cloud_mode_is_enabled(): void
    {
        config()->set('app.cloud.enabled', true);

        $this->get(route('home'))
            ->assertOk()
            ->assertViewIs('home');
    }

    public function test_disabled_cloud_mode_sends_guest_homepage_visitors_to_admin_login(): void
    {
        config()->set('app.cloud.enabled', false);
        config()->set('app.cloud.guest_entry', 'admin');

        $this->get(route('home'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_disabled_cloud_mode_sends_guest_homepage_visitors_to_customer_login(): void
    {
        config()->set('app.cloud.enabled', false);
        config()->set('app.cloud.guest_entry', 'customer');

        $this->get(route('home'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_disabled_cloud_mode_redirects_guest_login_forms_to_configured_entry(): void
    {
        config()->set('app.cloud.enabled', false);
        config()->set('app.cloud.guest_entry', 'customer');

        $this->get(route('auth.login'))
            ->assertRedirect(route('customer.login'));

        config()->set('app.cloud.guest_entry', 'admin');

        $this->get(route('customer.login'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_registration_is_disabled_when_cloud_mode_is_disabled(): void
    {
        config()->set('app.cloud.enabled', false);

        $this->get(route('auth.register'))
            ->assertNotFound();

        $this->post(route('auth.register.store'))
            ->assertForbidden();
    }

    public function test_authenticated_admins_are_sent_to_the_admin_dashboard(): void
    {
        config()->set('app.cloud.enabled', false);

        $this->actingAs(User::factory()->make(['id' => 1]));

        $this->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_customers_are_sent_to_the_customer_dashboard(): void
    {
        config()->set('app.cloud.enabled', false);

        $customer = new Customer([
            'tenant_id' => 'tenant-1',
            'email' => 'customer@example.com',
            'name' => 'Customer User',
        ]);
        $customer->id = 1;

        $this->actingAs($customer, 'customer');

        $this->get(route('home'))
            ->assertRedirect(route('customer.dashboard'));
    }
}
