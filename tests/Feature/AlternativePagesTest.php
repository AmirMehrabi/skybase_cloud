<?php

namespace Tests\Feature;

use Tests\TestCase;

class AlternativePagesTest extends TestCase
{
    public function test_splynx_alternative_page_is_public(): void
    {
        $response = $this->get(route('alternatives.splynx'));

        $response->assertStatus(200);
        $response->assertSee('Considering a Splynx alternative? Start with your working day.');
        $response->assertSee('Splynx Alternative | SkyBase Cloud for MikroTik ISPs');
        $response->assertSee('Splynx pricing page');
    }

    public function test_sonar_alternative_page_is_public(): void
    {
        $response = $this->get(route('alternatives.sonar'));

        $response->assertStatus(200);
        $response->assertSee('Looking for a Sonar alternative? Bring your real requirements.');
        $response->assertSee('Sonar Alternative | SkyBase Cloud for MikroTik ISPs');
        $response->assertSee('Sonar pricing page');
    }

    public function test_homepage_links_to_alternative_pages(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee(route('alternatives.splynx', absolute: false));
        $response->assertSee(route('alternatives.sonar', absolute: false));
    }

    public function test_powercode_comparison_is_public_and_identifies_network_dependencies(): void
    {
        $response = $this->get(route('alternatives.powercode'));

        $response->assertOk();
        $response->assertSee('Powercode Alternative for MikroTik ISPs');
        $response->assertSee('Powercode product overview');
        $response->assertSee('Command');
        $response->assertSee('Link');
        $response->assertSee('Get a quote');
        $response->assertSee('Do not assume a direct replacement.');
    }

    public function test_comparisons_include_sources_dates_migration_guidance_and_canonical_urls(): void
    {
        foreach (['splynx', 'sonar', 'powercode'] as $competitor) {
            $response = $this->get(route('alternatives.'.$competitor));

            $response->assertOk();
            $response->assertSee('October 30, 2026');
            $response->assertDontSee('June 12, 2026');
            $response->assertSee('This comparison is written by SkyBase.');
            $response->assertSee(route('guides.isp-software-migration'), false);
            $response->assertSee('<link rel="canonical" href="'.route('alternatives.'.$competitor).'">', false);
            $response->assertSee('images/product/customer.webp', false);
        }
    }

    public function test_public_pricing_examples_include_their_assumptions(): void
    {
        $this->get(route('alternatives.splynx'))
            ->assertSee('$255/month')
            ->assertSee('$400/month')
            ->assertSee('without annual discounts or optional add-ons');
        $this->get(route('alternatives.sonar'))
            ->assertSee('$500/month minimum')
            ->assertSee('$750/month')
            ->assertSee('with an annual contract');
    }
}
