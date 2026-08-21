<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketingLeadSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_inquiry_is_saved_to_the_database(): void
    {
        Http::fake();

        $response = $this->post(route('contact.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+33 7 58 35 14 73',
            'company_name' => 'SkyNet Fiber',
            'subject' => 'Pricing question',
            'message' => 'We would like to learn more about your platform for our business.',
        ]);

        $response->assertRedirect(route('contact.show'));
        $response->assertSessionHas('contact_success');

        $this->assertDatabaseHas('contact_inquiries', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Pricing question',
            'source_page' => 'contact',
        ]);
    }

    public function test_demo_request_is_saved_to_the_database(): void
    {
        Http::fake();

        $response = $this->post(route('demo-requests.store'), [
            'requested_plan' => 'Standard',
            'business_name' => 'SkyNet Fiber',
            'contact_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+33 7 58 35 14 73',
            'country' => 'France',
            'company_website' => 'https://example.com',
            'customer_count' => 320,
            'current_system' => 'Spreadsheets',
            'deployment_timeline' => 'This quarter',
            'message' => 'We want to see provisioning and billing workflows.',
        ]);

        $response->assertRedirect(route('pricing'));
        $response->assertSessionHas('demo_request_success');

        $this->assertDatabaseHas('demo_requests', [
            'requested_plan' => 'Standard',
            'business_name' => 'SkyNet Fiber',
            'contact_name' => 'Jane Doe',
            'customer_count' => 320,
            'source_page' => 'pricing',
        ]);
    }

    public function test_homepage_guided_setup_request_returns_to_the_form(): void
    {
        Http::fake();

        $response = $this->post(route('demo-requests.store'), [
            'requested_plan' => 'Guided setup from homepage',
            'business_name' => 'Clear Sky Fiber',
            'contact_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'country' => 'France',
            'customer_count' => 120,
            'message' => 'We want to simplify customer and router follow-up.',
            'source_page' => 'home',
        ]);

        $response->assertRedirect(route('home').'#guided-setup');
        $response->assertSessionHas('demo_request_success');

        $this->assertDatabaseHas('demo_requests', [
            'requested_plan' => 'Guided setup from homepage',
            'business_name' => 'Clear Sky Fiber',
            'customer_count' => 120,
            'source_page' => 'home',
        ]);
    }
}
