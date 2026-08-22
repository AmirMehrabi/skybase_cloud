<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoLandingPagesTest extends TestCase
{
    public function test_search_landing_pages_are_public_and_self_canonicalize(): void
    {
        $pages = [
            'seo.wisp-management-software' => 'One cloud workspace for running a growing WISP.',
            'seo.wisp-crm' => 'A customer record that understands the internet service behind it.',
            'seo.mikrotik-isp-software' => 'Connect subscriber operations to your MikroTik network.',
        ];

        foreach ($pages as $routeName => $headline) {
            $response = $this->get(route($routeName));

            $response->assertOk();
            $response->assertSee($headline);
            $response->assertSee('<link rel="canonical" href="'.route($routeName).'">', false);
            $response->assertSee('Free for up to 40 subscribers.');
        }
    }

    public function test_homepage_keeps_its_message_and_links_to_search_landing_pages(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Run your ISP.');
        $response->assertSee('Not your software.');
        $response->assertSee('WISP & ISP Management for MikroTik Operators');
        $response->assertSee('<link rel="canonical" href="'.route('home').'">', false);
        $response->assertSee(route('seo.wisp-management-software', absolute: false));
        $response->assertSee(route('seo.wisp-crm', absolute: false));
        $response->assertSee(route('seo.mikrotik-isp-software', absolute: false));
    }

    public function test_sitemap_contains_only_public_marketing_pages(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(route('home'), false);
        $response->assertSee(route('seo.wisp-management-software'), false);
        $response->assertSee(route('seo.wisp-crm'), false);
        $response->assertSee(route('seo.mikrotik-isp-software'), false);
        $response->assertDontSee('/auth/login', false);
        $response->assertDontSee('/dashboard', false);
        $response->assertDontSee('/customer-portal', false);
    }

    public function test_robots_file_advertises_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robots);
        $this->assertStringContainsString('Sitemap: https://skybase.app/sitemap.xml', $robots);
    }
}
