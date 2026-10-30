<?php

namespace Tests\Feature;

use App\Services\DummySkybasePaymentGateway;
use LogicException;
use Tests\TestCase;

class DummySkybasePaymentGatewayTest extends TestCase
{
    public function test_simulation_requires_explicit_enablement(): void
    {
        config(['skybase_billing.enabled' => true, 'skybase_billing.dummy_gateway_enabled' => false]);
        $this->expectException(LogicException::class);
        app(DummySkybasePaymentGateway::class)->simulate('invoice', 6900, 'USD');
    }

    public function test_simulation_is_labeled_and_preserves_terms(): void
    {
        config(['skybase_billing.enabled' => true, 'skybase_billing.dummy_gateway_enabled' => true]);
        $payment = app(DummySkybasePaymentGateway::class)->simulate('invoice', 6900, 'USD');
        $this->assertTrue($payment['simulated']);
        $this->assertSame(6900, $payment['amount_minor']);
        $this->assertSame('invoice', $payment['invoice_id']);
        $this->assertStringStartsWith('dummy-', $payment['reference']);
    }

    public function test_production_rejects_simulated_payments(): void
    {
        config(['skybase_billing.enabled' => true, 'skybase_billing.dummy_gateway_enabled' => true]);
        $this->app->instance('env', 'production');
        $this->expectException(LogicException::class);
        app(DummySkybasePaymentGateway::class)->simulate('invoice', 6900, 'USD');
    }
}
