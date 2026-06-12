<?php

namespace Tests\Feature;

use Tests\TestCase;

class AlternativePagesTest extends TestCase
{
    public function test_splynx_alternative_page_is_public(): void
    {
        $response = $this->get(route('alternatives.splynx'));

        $response->assertStatus(200);
        $response->assertSee('SkyBase vs Splynx: a simpler alternative for MikroTik ISPs.');
        $response->assertSee('Splynx Alternative | SkyBase Cloud for MikroTik ISPs');
        $response->assertSee('Splynx pricing page');
    }

    public function test_sonar_alternative_page_is_public(): void
    {
        $response = $this->get(route('alternatives.sonar'));

        $response->assertStatus(200);
        $response->assertSee('SkyBase vs Sonar: an affordable alternative for MikroTik ISPs.');
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
}
