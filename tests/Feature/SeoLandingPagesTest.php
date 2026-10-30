<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoLandingPagesTest extends TestCase
{
    public function test_search_landing_pages_are_public_and_self_canonicalize(): void
    {
        $pages = [
            'seo.wisp-management-software' => 'Your WISP has enough moving parts. Keep the work together.',
            'seo.wisp-crm' => 'The customer is on the phone. You have the whole story.',
            'seo.mikrotik-isp-software' => 'Your MikroTik network. Your customers. Finally on the same page.',
            'seo.isp-crm' => 'One place to pick up where the last conversation left off.',
            'seo.isp-billing-software' => 'Billing day should feel like an ordinary day.',
            'guides.choosing-isp-crm' => 'Bring a normal working day to the demo.',
            'guides.isp-software-migration' => 'Changing software is a project. Give it a plan.',
            'guides.mikrotik-billing' => 'An unpaid invoice and a broken connection need different answers.',
        ];

        foreach ($pages as $routeName => $headline) {
            $response = $this->get(route($routeName));

            $response->assertOk();
            $response->assertSee($headline);
            $response->assertHeaderMissing('X-Robots-Tag');
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
        foreach (['seo.isp-crm', 'seo.isp-billing-software', 'alternatives.powercode', 'guides.choosing-isp-crm', 'guides.isp-software-migration', 'guides.mikrotik-billing'] as $routeName) {
            $response->assertSee(route($routeName), false);
        }

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

    public function test_product_pages_show_redacted_local_screenshots(): void
    {
        foreach ([
            'seo.wisp-crm' => 'customer',
            'seo.isp-crm' => 'customer',
            'seo.isp-billing-software' => 'billing',
            'seo.mikrotik-isp-software' => 'subscription',
            'seo.wisp-management-software' => 'dashboard',
        ] as $routeName => $image) {
            $response = $this->get(route($routeName));

            $response->assertOk();
            $response->assertSee(asset('images/product/'.$image.'.webp'), false);
            $response->assertSee('Private details have been redacted.');
            $response->assertSee('width="905" height="605"', false);
            $dimensions = getimagesize(public_path('images/product/'.$image.'.webp'));
            $this->assertSame([905, 605], [$dimensions[0], $dimensions[1]]);
        }
    }

    public function test_marketing_canonical_and_open_graph_urls_use_the_configured_host_without_tracking_parameters(): void
    {
        config(['app.url' => 'https://example.test']);

        $response = $this->get('https://www.example.test/wisp-crm?utm_source=example');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="https://example.test/wisp-crm">', false);
        $response->assertSee('<meta property="og:url" content="https://example.test/wisp-crm">', false);
    }

    public function test_all_new_pages_are_linked_from_the_shared_footer(): void
    {
        $response = $this->get(route('home'));

        foreach (['seo.isp-crm', 'seo.isp-billing-software', 'alternatives.powercode', 'guides.choosing-isp-crm', 'guides.isp-software-migration', 'guides.mikrotik-billing'] as $routeName) {
            $response->assertSee(route($routeName), false);
        }
    }

    public function test_conversion_marker_requires_a_successful_submission(): void
    {
        $this->get(route('home'))->assertSee('data-lead-submitted=""', false);
        $this->withSession(['demo_request_success' => 'Received'])
            ->get(route('home'))
            ->assertSee('data-lead-submitted="guided_setup"', false);
        $this->withSession(['demo_request_success' => null, 'contact_success' => 'Received'])
            ->get(route('contact.show'))
            ->assertSee('data-lead-submitted="contact"', false);
    }

    public function test_sitemap_uses_the_same_canonical_host_as_marketing_pages(): void
    {
        config(['app.url' => 'https://example.test']);

        $response = $this->get('https://www.example.test/sitemap.xml');

        $response->assertOk();
        $response->assertSee('<loc>https://example.test/isp-crm</loc>', false);
        $response->assertSee('<loc>https://example.test/alternatives/powercode</loc>', false);
        $response->assertDontSee('https://www.example.test', false);
    }
}
