<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingTrustContentTest extends TestCase
{
    public function test_homepage_presents_a_focused_founder_led_conversion_path(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Run your ISP.');
        $response->assertSee('Not your software.');
        $response->assertSee('For small and growing MikroTik ISPs');
        $response->assertSee('Free forever');
        $response->assertSee('Representative SkyBase dashboard');
        $response->assertSee('A note from the founder');
        $response->assertSee('Abbie Barlowe');
        $response->assertSee('Ultech Solutions');
        $response->assertSee('Capable where it matters. Quiet everywhere else.');
        $response->assertSee('Try SkyBase');
        $response->assertSee('href="'.route('auth.register').'"', false);
        $response->assertSee('14-day trial. No credit card required. Free plan available for up to 40 subscribers.');
        $response->assertSee('Explore on your own, or let us help you get started.');
        $response->assertSee('Book a guided setup');
        $response->assertSee('Book my guided setup');
        $response->assertDontSee('Illustrative workspace');
        $response->assertDontSee('Frequently Asked Questions');
        $response->assertDontSee('Compare Splynx');
        $response->assertDontSee('Start Trial');
        $response->assertDontSee('24/7');
    }

    public function test_changelog_displays_the_latest_release_version(): void
    {
        $response = $this->get(route('changelog'));

        $response->assertOk();
        $response->assertSee('Current version 1.0.0');
        $response->assertDontSee('Current version 0.9.9');
    }
}
