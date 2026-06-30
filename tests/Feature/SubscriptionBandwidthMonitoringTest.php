<?php

namespace Tests\Feature;

use Tests\TestCase;

class SubscriptionBandwidthMonitoringTest extends TestCase
{
    public function test_bandwidth_history_route_is_available_without_the_legacy_png_route(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutesByName());

        $this->assertTrue($routes->has('subscriptions.bandwidth.history'));
        $this->assertTrue($routes->has('subscriptions.bandwidth.live'));
        $this->assertFalse($routes->has('subscriptions.bandwidth.graph'));
    }
}
