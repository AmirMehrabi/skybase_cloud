<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class FirstSubscriptionGuideTest extends TestCase
{
    public function test_pppoe_guide_is_public_and_contains_the_complete_journey(): void
    {
        $response = $this->get(route('guide'));

        $response->assertOk()
            ->assertViewHas('serviceType', 'pppoe')
            ->assertSee('Your first subscriber.')
            ->assertSee('Subscription Type to PPPoE')
            ->assertSee('PPP → PPPoE Servers')
            ->assertSee('System Managed')
            ->assertSee('One real connection')
            ->assertSee('PPP → Active Connections')
            ->assertSee('src-address=REGISTERED_ROUTER_IP')
            ->assertDontSee('Create the Wi-Fi login service');

        foreach (['before-you-start', 'router', 'customer', 'plan', 'ipam', 'routeros', 'subscription', 'test', 'troubleshooting'] as $chapter) {
            $response->assertSee('id="'.$chapter.'"', false)
                ->assertSee('href="#'.$chapter.'"', false);
        }
    }

    public function test_hotspot_guide_explains_the_actual_credential_and_addressing_flow(): void
    {
        $this->get(route('guide', ['service' => 'hotspot']))
            ->assertOk()
            ->assertViewHas('serviceType', 'hotspot')
            ->assertSee('Subscription Type to Hotspot')
            ->assertSee('IP → Hotspot → Hotspot Setup')
            ->assertSee('radius-accounting=yes')
            ->assertSee('Router Managed')
            ->assertSee('Why does a Hotspot user have')
            ->assertSee('IP → Hotspot → Active')
            ->assertSee('subscription-hotspot.webp')
            ->assertDontSee('Create the PPPoE service');
    }

    public function test_unknown_or_array_service_values_fall_back_to_pppoe(): void
    {
        foreach (['unknown', ['hotspot']] as $service) {
            $this->get(route('guide', ['service' => $service]))
                ->assertOk()
                ->assertViewHas('serviceType', 'pppoe');
        }
    }

    public function test_signed_in_users_can_read_the_guide_without_a_dashboard_redirect(): void
    {
        $this->actingAs(User::factory()->make(['id' => 1]))
            ->get(route('guide'))
            ->assertOk()
            ->assertViewIs('modules.guide.index');
    }

    public function test_setup_links_and_local_screenshots_are_available(): void
    {
        $response = $this->get(route('guide'));

        foreach (['routers.create', 'customers.create', 'plans.create', 'ipam.pools.create', 'subscriptions.create'] as $route) {
            $response->assertSee('href="'.route($route).'" target="_blank" rel="noopener"', false);
        }

        foreach (['router', 'customer', 'plan', 'ipam', 'subscription-pppoe', 'subscription-hotspot'] as $image) {
            $this->assertFileExists(public_path('images/guide/'.$image.'.webp'));
        }

        $response->assertSee('Show the full screenshot')
            ->assertDontSee('admin.onlimeafrica.com')
            ->assertDontSee('admin@skybase.app');
    }

    public function test_home_navigation_and_sitemap_include_the_guide(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('href="'.route('guide').'"', false);
        $this->get(route('sitemap'))->assertOk()->assertSee(route('guide'), false);
    }
}
