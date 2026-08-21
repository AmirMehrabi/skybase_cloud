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
        $response->assertSee('Current version 0.9.9');
        $response->assertDontSee('Current version 0.9.8');
    }
}
