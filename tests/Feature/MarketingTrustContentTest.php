<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingTrustContentTest extends TestCase
{
    public function test_homepage_uses_guided_setup_as_the_primary_conversion_path(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Book a guided setup');
        $response->assertSee('Free forever up to 40 subscribers');
        $response->assertSee('Illustrative SkyBase workspace');
        $response->assertSee('A letter from the founder');
        $response->assertSee('Abbie Barlowe');
        $response->assertSee('What customers say');
        $response->assertSee('Sample testimonial');
        $response->assertSee('Sample customer');
        $response->assertSee('A product shaped by customer feedback');
        $response->assertDontSee('Early customer proof');
        $response->assertDontSee('Founder story and photograph coming next');
        $response->assertDontSee('Start Trial');
        $response->assertDontSee('24/7');
    }

    public function test_changelog_displays_the_latest_release_version(): void
    {
        $response = $this->get(route('changelog'));

        $response->assertOk();
        $response->assertSee('Current version 0.9.8');
        $response->assertDontSee('Current version 0.9.6');
    }
}
