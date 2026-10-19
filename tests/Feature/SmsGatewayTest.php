<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use App\Services\SmsGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SmsGatewayTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['id' => (string) Str::uuid(), 'name' => 'SMS', 'slug' => 'sms-test', 'company_name' => 'SMS', 'email' => 'sms@example.com', 'status' => 'active']);
        app()->instance('current_tenant', $this->tenant);
        Http::preventStrayRequests();
    }

    public function test_twilio_uses_credentials_and_preserves_text(): void
    {
        Setting::set('sms.gateway', ['provider' => 'twilio', 'twilio' => ['sid' => 'ACtest', 'token' => 'top-secret', 'sender' => '+12025550100']], 'json', $this->tenant->id);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201)]);
        $result = app(SmsGatewayService::class)->send($this->tenant->id, '+989121234567', ['sms_body' => 'سلام test']);
        $this->assertSame('SM123', $result['id']);
        Http::assertSent(fn ($request) => $request['Body'] === 'سلام test' && $request['From'] === '+12025550100');
        $this->assertStringNotContainsString('top-secret', Setting::query()->first()->getRawOriginal('value'));
    }

    public function test_smsportal_test_mode_is_honored_and_reported_as_simulated(): void
    {
        Setting::set('sms.gateway', ['provider' => 'smsportal', 'smsportal' => ['client_id' => 'client', 'secret' => 'secret', 'test_mode' => true]], 'json', $this->tenant->id);
        Http::fake(['rest.smsportal.com/*' => Http::response(['eventId' => 123, 'messages' => 1, 'parts' => 1, 'errorReport' => ['faults' => []]])]);
        $result = app(SmsGatewayService::class)->send($this->tenant->id, '+12025550100', ['sms_body' => 'test']);
        $this->assertSame('simulated', $result['status']);
        Http::assertSent(fn ($request) => $request['sendOptions']['testMode'] === true);
    }

    public function test_smsportal_uses_event_id_and_rejects_a_zero_message_result(): void
    {
        Setting::set('sms.gateway', ['provider' => 'smsportal', 'smsportal' => ['client_id' => 'client', 'secret' => 'secret', 'test_mode' => false]], 'json', $this->tenant->id);
        Http::fake(['rest.smsportal.com/*' => Http::sequence()->push(['EventId' => 12345678901, 'Messages' => 1, 'Parts' => 1])->push(['eventId' => 123, 'messages' => 0, 'errorReport' => ['optedOuts' => 1]])]);
        $result = app(SmsGatewayService::class)->send($this->tenant->id, '+12025550100', ['sms_body' => 'test']);
        $this->assertSame('12345678901', $result['id']);
        $this->assertSame('accepted', $result['status']);
        $this->expectException(\RuntimeException::class);
        app(SmsGatewayService::class)->send($this->tenant->id, '+12025550100', ['sms_body' => 'test']);
    }

    public function test_kavenegar_lookup_preserves_credentials_tokens(): void
    {
        Setting::set('sms.gateway', ['provider' => 'kavenegar', 'kavenegar' => ['api_key' => 'key']], 'json', $this->tenant->id);
        Http::fake(['api.kavenegar.com/*' => Http::response(['return' => ['status' => 200], 'entries' => [['messageid' => 123]]])]);
        $result = app(SmsGatewayService::class)->send($this->tenant->id, '+989121234567', ['sms_mode' => 'lookup', 'lookup_template' => 'internet-account', 'lookup_tokens' => ['token' => 'Ali', 'token2' => 'user_name', 'token3' => 'p_ass-word']]);
        $this->assertSame('123', $result['id']);
        Http::assertSent(fn ($request) => $request['token3'] === 'p_ass-word' && str_contains($request->url(), '/verify/lookup.json'));
    }

    public function test_authentication_test_does_not_send_a_message(): void
    {
        Setting::set('sms.gateway', ['provider' => 'kavenegar', 'kavenegar' => ['api_key' => 'key']], 'json', $this->tenant->id);
        Http::fake(['api.kavenegar.com/*' => Http::response(['return' => ['status' => 200]])]);
        app(SmsGatewayService::class)->testConnection($this->tenant->id);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/account/info.json') && $request->method() === 'GET');
    }

    public function test_provider_receipt_confirms_delivery(): void
    {
        Setting::set('sms.gateway', ['provider' => 'twilio', 'twilio' => ['sid' => 'ACtest', 'token' => 'secret']], 'json', $this->tenant->id);
        Http::fake(['api.twilio.com/*' => Http::response(['status' => 'delivered'])]);
        $this->assertSame('delivered', app(SmsGatewayService::class)->receipt($this->tenant->id, 'twilio', 'SM123'));
    }
}
