<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionIpdrTabTest extends TestCase
{
    use RefreshDatabase;

    private function subscription(): Subscription
    {
        $tenant = Tenant::create(['id' => (string) Str::uuid(), 'name' => 'Traffic ISP', 'slug' => 'traffic-isp', 'company_name' => 'Traffic ISP', 'email' => 'traffic@example.com', 'timezone' => 'UTC', 'status' => 'active']);
        $subscription = app(TenantContext::class)->run((string) $tenant->id, function () use ($tenant): Subscription {
            $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
            $plan = Plan::factory()->create(['tenant_id' => $tenant->id]);
            $router = Router::factory()->create(['tenant_id' => $tenant->id]);

            return Subscription::withoutEvents(fn () => Subscription::create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'plan_id' => $plan->id, 'router_id' => $router->id, 'subscription_code' => 'SUB-IPDR-TAB', 'name' => 'Traffic subscriber', 'connection_type' => 'pppoe', 'pppoe_username' => 'traffic', 'base_price' => 0, 'total_price' => 0, 'billing_cycle' => 'monthly', 'status' => 'active']));
        });
        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']));
        Http::preventStrayRequests();

        return $subscription;
    }

    public function test_ipdr_tab_uses_lazy_analytics_and_only_appears_when_enabled(): void
    {
        $subscription = $this->subscription();
        config(['ipdr.enabled' => false]);
        $this->get(route('subscriptions.show', $subscription))->assertOk()->assertDontSee('IPDR Traffic')->assertDontSee('subscription-ipdr-panel');
        config(['ipdr.enabled' => true]);
        $response = $this->get(route('subscriptions.show', ['subscription' => $subscription, 'tab' => 'ipdr']));
        $response->assertOk()->assertSee('IPDR Traffic')->assertSee('Top source IPs')->assertSee('Top destination IPs')->assertSee('Traffic over time');
        Http::assertNothingSent();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $panel = (new \DOMXPath($document))->query('//*[@id="subscription-ipdr-panel"]')->item(0);
        $this->assertSame('subscriptionIpdr({ url: '.Js::from(route('subscriptions.ipdr', $subscription)).' })', $panel->getAttribute('x-data'));
        $this->assertSame('sourceIp', (new \DOMXPath($document))->query('//*[@id="ipdr-source"]')->item(0)->getAttribute('x-model'));
    }

    public function test_analytics_filters_are_validated_and_forwarded_without_identity_overrides(): void
    {
        $subscription = $this->subscription();
        config(['ipdr.enabled' => true, 'ipdr.url' => 'https://ipdr.example', 'ipdr.integration_token' => str_repeat('s', 48)]);
        Http::fake(['ipdr.example/*' => Http::response(['analytics' => ['time_series' => []]])]);
        $this->getJson(route('subscriptions.ipdr', $subscription).'?analytics=1&source_ip=1.1.1.1&destination_ip=10.0.0.2&subscription_id=999&tenant_id=other')->assertOk();
        Http::assertSent(fn ($request) => $request['analytics'] === '1' && $request['source_ip'] === '1.1.1.1' && $request['destination_ip'] === '10.0.0.2' && $request['subscription_id'] === (string) $subscription->id && ! isset($request['tenant_id']));
        $this->getJson(route('subscriptions.ipdr', $subscription).'?source_ip=invalid')->assertUnprocessable();
        $this->getJson(route('subscriptions.ipdr', $subscription).'?from=2024-01-01&to=2026-01-01')->assertUnprocessable()->assertJsonValidationErrors('to');
    }

    public function test_legacy_report_link_opens_the_embedded_tab_without_an_extra_request(): void
    {
        $subscription = $this->subscription();
        config(['ipdr.enabled' => true]);
        $this->get(route('subscriptions.ipdr', $subscription))->assertRedirect(route('subscriptions.show', ['subscription' => $subscription, 'tab' => 'ipdr']));
        Http::assertNothingSent();
    }
}
