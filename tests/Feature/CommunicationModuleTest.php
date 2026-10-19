<?php

namespace Tests\Feature;

use App\Jobs\DeliverCommunication;
use App\Models\Campaign;
use App\Models\CommunicationTemplate;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MessageDelivery;
use App\Models\NotificationIncident;
use App\Models\NotificationPreference;
use App\Models\Router;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\CommunicationDeliveryService;
use App\Services\MessageTemplateService;
use App\Services\NotificationIncidentService;
use App\Services\SmsGatewayService;
use App\Services\TenantContext;
use App\Services\TenantNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommunicationModuleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = $this->makeTenant();
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($this->admin);
        app()->instance('current_tenant', $this->tenant);
        Setting::set('notifications.rules', ['enabled' => true, 'channels' => ['sms' => true, 'email' => true, 'in_app' => true]], 'json', $this->tenant->id);
        Setting::set('sms.gateway', ['provider' => 'twilio', 'twilio' => ['sid' => 'AC123', 'token' => 'secret', 'sender' => '+12025550100']], 'json', $this->tenant->id);
    }

    private function makeTenant(): Tenant
    {
        $slug = 'net-'.Str::lower(Str::random(8));

        return Tenant::create(['id' => (string) Str::uuid(), 'name' => $slug, 'slug' => $slug, 'company_name' => $slug, 'email' => $slug.'@example.com', 'timezone' => 'Asia/Tehran', 'status' => 'active']);
    }

    private function customer(array $attributes = []): Customer
    {
        $customer = Customer::factory()->active()->create($attributes + ['tenant_id' => $this->tenant->id, 'mobile' => '+989121234567']);
        NotificationPreference::query()->create(['tenant_id' => $this->tenant->id, 'notifiable_type' => $customer->getMorphClass(), 'notifiable_id' => $customer->id, 'notifications_enabled' => true, 'in_app_enabled' => true, 'email_enabled' => true, 'sms_enabled' => true]);

        return $customer;
    }

    public function test_module_pages_render_and_do_not_expose_gateway_secrets(): void
    {
        foreach (['campaigns.index', 'campaigns.create', 'communications.index', 'communications.create', 'communications.deliveries', 'communications.incidents', 'settings.sms'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('value="secret"', false);
        }
    }

    public function test_campaign_is_a_draft_until_reviewed_then_freezes_content_and_audience(): void
    {
        $customer = $this->customer();
        $this->post(route('campaigns.store'), ['name' => 'Maintenance', 'purpose' => 'service', 'channels' => ['sms'], 'sms_body' => 'Hello {{customer_name}}', 'audience_mode' => 'snapshot', 'filters' => ['status' => 'active']])->assertSessionHasNoErrors()->assertRedirect();
        $campaign = Campaign::query()->firstOrFail();
        $this->assertSame('draft', $campaign->status);
        $this->assertSame([$customer->id], $campaign->recipient_ids);
        $this->get(route('campaigns.show', $campaign))->assertOk()->assertSee('Eligible channel deliveries');
        $this->post(route('campaigns.action', $campaign), ['action' => 'send'])->assertRedirect();
        app(CampaignService::class)->materialize($campaign->fresh());
        app(CampaignService::class)->materialize($campaign->fresh());
        $this->assertSame(1, $campaign->deliveries()->count());
        $this->assertStringContainsString($customer->full_name, $campaign->deliveries()->first()->content['sms_body']);
    }

    public function test_cross_tenant_recipient_and_resource_access_are_rejected(): void
    {
        $other = $this->makeTenant();
        $foreignCustomer = Customer::factory()->active()->create(['tenant_id' => $other->id]);
        $foreignCampaign = Campaign::query()->create(['tenant_id' => $other->id, 'name' => 'Private', 'channels' => ['sms'], 'content' => [], 'audience_filters' => [], 'recipient_ids' => []]);
        $this->get(route('campaigns.show', $foreignCampaign))->assertNotFound();
        $this->post(route('campaigns.store'), ['name' => 'Bad', 'purpose' => 'service', 'channels' => ['sms'], 'sms_body' => 'Hello', 'audience_mode' => 'snapshot', 'filters' => ['customer_ids' => [$foreignCustomer->id]]])->assertSessionHasErrors('filters.customer_ids.0');
    }

    public function test_delivery_is_sent_once_even_if_the_job_runs_twice(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201)]);
        $service = app(CommunicationDeliveryService::class);
        $delivery = $service->create($this->tenant->id, 'same', ['channel' => 'sms', 'destination' => '+989121234567', 'content' => ['sms_body' => 'Hello']]);
        $same = $service->create($this->tenant->id, 'same', ['channel' => 'sms', 'content' => []]);
        $this->assertSame($delivery->id, $same->id);
        (new DeliverCommunication($this->tenant->id, $delivery->id))->handle(app(TenantContext::class), $service);
        (new DeliverCommunication($this->tenant->id, $delivery->id))->handle(app(TenantContext::class), $service);
        Http::assertSentCount(1);
        $this->assertSame('accepted', $delivery->fresh()->status);
        $this->assertStringNotContainsString('Hello', $delivery->getRawOriginal('content'));
    }

    public function test_retryable_rejection_and_uncertain_timeout_are_distinct(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response([], 429)]);
        $service = app(CommunicationDeliveryService::class);
        $delivery = $service->create($this->tenant->id, 'rate', ['channel' => 'sms', 'destination' => '+989121234567', 'content' => ['sms_body' => 'Hello']]);
        $service->deliver($delivery);
        $this->assertSame('retrying', $delivery->fresh()->status);
        $this->assertTrue($delivery->fresh()->available_at->isFuture());
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $unknown = $service->create($this->tenant->id, 'timeout', ['channel' => 'sms', 'destination' => '+989121234567', 'content' => ['sms_body' => 'Hello']]);
        $service->deliver($unknown);
        $this->assertSame('unknown', $unknown->fresh()->status);
    }

    public function test_notifications_use_preferences_and_idempotency_independently_of_in_app(): void
    {
        $customer = $this->customer();
        CommunicationTemplate::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Invoice', 'event_key' => 'billing.invoice_created', 'recipient_type' => 'customer', 'enabled' => true, 'channels' => ['sms'], 'sms_body' => '{{body}}']);
        $service = app(TenantNotificationService::class);
        $payload = ['title' => 'Invoice', 'body' => 'Invoice is ready', 'event_identity' => 'invoice:1'];
        $service->notifyCustomer($customer, 'billing.invoice_created', $payload);
        $service->notifyCustomer($customer, 'billing.invoice_created', $payload);
        $this->assertSame(1, MessageDelivery::query()->count());
        NotificationPreference::query()->where('notifiable_id', $customer->id)->update(['sms_enabled' => false]);
        $service->notifyCustomer($customer, 'billing.invoice_created', ['event_identity' => 'invoice:2']);
        $this->assertSame(1, MessageDelivery::query()->count());
    }

    public function test_scheduler_recovers_pending_outbox_and_does_not_dispatch_other_tenant_work(): void
    {
        Queue::fake();
        app(CommunicationDeliveryService::class)->create($this->tenant->id, 'outbox', ['channel' => 'sms', 'content' => ['sms_body' => 'Hi'], 'destination' => '+989121234567']);
        app(CommunicationDeliveryService::class)->dispatchDue($this->tenant->id);
        Queue::assertPushed(DeliverCommunication::class, fn ($job) => $job->tenantId === $this->tenant->id && $job->queue === 'notifications');
    }

    public function test_pause_cancellation_and_expiry_prevent_remaining_sends(): void
    {
        Http::fake();
        $campaign = Campaign::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Paused', 'status' => 'paused', 'channels' => ['sms'], 'content' => [], 'audience_filters' => [], 'recipient_ids' => []]);
        $delivery = app(CommunicationDeliveryService::class)->create($this->tenant->id, 'pause', ['campaign_id' => $campaign->id, 'channel' => 'sms', 'content' => ['sms_body' => 'Hi'], 'destination' => '+989121234567']);
        app(CommunicationDeliveryService::class)->deliver($delivery);
        $this->assertSame('pending', $delivery->fresh()->status);
        $this->post(route('campaigns.action', $campaign), ['action' => 'cancel'])->assertRedirect();
        $this->assertSame('cancelled', $delivery->fresh()->status);
        $expired = app(CommunicationDeliveryService::class)->create($this->tenant->id, 'expired', ['channel' => 'sms', 'content' => [], 'destination' => '+989121234567', 'expires_at' => now()->subSecond()]);
        app(CommunicationDeliveryService::class)->deliver($expired);
        $this->assertSame('skipped', $expired->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_scheduled_time_uses_tenant_timezone_and_does_not_send_early(): void
    {
        $this->customer();
        $campaign = Campaign::factory()->create(['tenant_id' => $this->tenant->id, 'recipient_ids' => Customer::query()->pluck('id')->all()]);
        $local = now()->addDay()->timezone('Asia/Tehran')->format('Y-m-d\TH:i');
        $this->post(route('campaigns.action', $campaign), ['action' => 'schedule', 'scheduled_at' => $local])->assertSessionHasNoErrors();
        $this->assertSame($local, $campaign->fresh()->scheduled_at->timezone('Asia/Tehran')->format('Y-m-d\TH:i'));
        app(CampaignService::class)->materialize($campaign->fresh());
        $this->assertSame(0, $campaign->deliveries()->count());
    }

    public function test_incident_threshold_escalation_acknowledgement_and_recovery(): void
    {
        $router = Router::factory()->create(['tenant_id' => $this->tenant->id]);
        Setting::set('notifications.incident_policy', ['enabled' => true, 'failure_threshold' => 3, 'recovery_threshold' => 2, 'escalation_minutes' => [5]], 'json', $this->tenant->id);
        foreach (['network.router_degraded', 'network.router_recovered'] as $event) {
            CommunicationTemplate::factory()->create(['tenant_id' => $this->tenant->id, 'event_key' => $event, 'recipient_type' => 'admin', 'enabled' => true, 'channels' => ['sms'], 'sms_body' => '{{router_name}} {{title}}', 'recipients' => [['channel' => 'sms', 'destination' => '+989121234567', 'stage' => 0]]]);
        }
        $service = app(NotificationIncidentService::class);
        $service->observe($router, false);
        $this->travel(1)->minutes();
        $service->observe($router, false);
        $this->assertSame(0, MessageDelivery::query()->count());
        $this->travel(1)->minutes();
        $service->observe($router, false);
        $incident = NotificationIncident::query()->firstOrFail();
        $this->assertSame('open', $incident->status);
        $this->assertSame(1, MessageDelivery::query()->count());
        $this->travel(6)->minutes();
        $service->escalate($this->tenant->id);
        $this->assertSame(2, MessageDelivery::query()->count());
        $this->post(route('communications.incident-action', $incident), ['action' => 'acknowledge'])->assertRedirect();
        $escalation = MessageDelivery::query()->latest('id')->first();
        $this->assertStringContainsString('acknowledged', app(CommunicationDeliveryService::class)->skipReason($escalation));
        $service->observe($router, true);
        $this->travel(1)->minutes();
        $service->observe($router, true);
        $this->assertSame('resolved', $incident->fresh()->status);
        $this->assertSame(3, MessageDelivery::query()->count());
    }

    public function test_campaign_suppresses_duplicate_destinations_and_records_missing_contacts(): void
    {
        $this->customer();
        $this->customer();
        $this->customer(['mobile' => null, 'phone' => null]);
        $campaign = Campaign::factory()->create(['tenant_id' => $this->tenant->id, 'status' => 'scheduled', 'audience_mode' => 'dynamic', 'scheduled_at' => now()]);
        app(CampaignService::class)->materialize($campaign);
        $this->assertSame(3, $campaign->deliveries()->count());
        $this->assertSame(2, $campaign->deliveries()->where('status', 'skipped')->count());
    }

    public function test_template_edit_and_delivery_views_render_with_records(): void
    {
        $template = CommunicationTemplate::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->get(route('communications.edit', $template))->assertOk();
        app(CommunicationDeliveryService::class)->create($this->tenant->id, 'view', ['channel' => 'sms', 'destination' => '+989121234567', 'content' => ['sms_body' => 'Snapshot']]);
        $this->get(route('communications.deliveries'))->assertOk()->assertSee('Snapshot');
    }

    public function test_stale_job_cannot_bypass_retry_delay(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response([], 429)]);
        $service = app(CommunicationDeliveryService::class);
        $delivery = $service->create($this->tenant->id, 'stale', ['channel' => 'sms', 'destination' => '+989121234567', 'content' => ['sms_body' => 'Hello']]);
        $stale = MessageDelivery::query()->findOrFail($delivery->id);
        $service->deliver($delivery);
        $service->deliver($stale);
        Http::assertSentCount(1);
        $this->assertSame('retrying', $delivery->fresh()->status);
    }

    public function test_gateway_save_keeps_blank_secrets_and_test_send_creates_a_delivery(): void
    {
        $this->put(route('settings.update.sms'), ['provider' => 'twilio', 'rate_per_minute' => 20, 'twilio' => ['sid' => 'AC123', 'token' => '', 'sender' => '+12025550100']])->assertSessionHasNoErrors();
        $this->assertSame('secret', app(SmsGatewayService::class)->settings($this->tenant->id)['twilio']['token']);
        $this->post(route('settings.test.sms'), ['action' => 'send', 'destination' => '+989121234567', 'message' => 'Test send'])->assertSessionHas('success');
        $this->assertSame(1, MessageDelivery::query()->count());
    }

    public function test_unknown_template_variables_are_rejected_and_templates_are_versioned(): void
    {
        $data = ['name' => 'Welcome', 'recipient_type' => 'customer', 'enabled' => true, 'channels' => ['sms'], 'sms_mode' => 'plain', 'sms_body' => 'Hello {{customer_name}}'];
        $this->post(route('communications.store'), $data)->assertSessionHasNoErrors();
        $template = CommunicationTemplate::query()->firstOrFail();
        $this->put(route('communications.update', $template), array_replace($data, ['sms_body' => 'Hello {{missing_variable}}']))->assertSessionHasErrors('sms_body');
        $this->put(route('communications.update', $template), array_replace($data, ['sms_body' => 'Updated {{customer_name}}']))->assertSessionHasNoErrors();
        $this->assertSame(2, $template->fresh()->version);
    }

    public function test_full_outbox_command_processes_due_campaigns_without_tenant_context_leaks(): void
    {
        Queue::fake();
        $customer = $this->customer();
        $campaign = Campaign::factory()->create(['tenant_id' => $this->tenant->id, 'created_by' => $this->admin->id, 'status' => 'scheduled', 'scheduled_at' => now()->subMinute(), 'recipient_ids' => [$customer->id]]);
        auth('web')->forgetUser();
        app()->forgetInstance('current_tenant');
        $this->artisan('communications:process')->assertSuccessful();
        $this->assertNull(app(TenantContext::class)->id());
        app(TenantContext::class)->run($this->tenant->id, function () use ($campaign): void {
            $this->assertSame('sending', $campaign->fresh()->status);
            $this->assertSame(1, MessageDelivery::query()->count());
        });
        Queue::assertPushed(DeliverCommunication::class);
    }

    public function test_payment_before_sending_suppresses_an_invoice_reminder(): void
    {
        $customer = $this->customer();
        $invoice = Invoice::query()->create(['tenant_id' => $this->tenant->id, 'customer_id' => $customer->id, 'invoice_number' => 'INV-PAID', 'billing_period_start' => today()->startOfMonth(), 'billing_period_end' => today()->endOfMonth(), 'issue_date' => today(), 'due_date' => today(), 'status' => 'paid', 'total' => 100, 'paid_amount' => 100, 'balance_due' => 0]);
        $delivery = app(CommunicationDeliveryService::class)->create($this->tenant->id, 'paid-reminder', ['event_key' => 'billing.invoice_reminder_0', 'channel' => 'sms', 'destination' => $customer->mobile, 'content' => ['sms_body' => 'Pay invoice'], 'context' => ['invoice_id' => $invoice->id, 'invoice_unpaid' => true]]);
        app(CommunicationDeliveryService::class)->deliver($delivery);
        $this->assertSame('skipped', $delivery->fresh()->status);
        $this->assertStringContainsString('paid', $delivery->fresh()->last_error);
    }

    public function test_a_different_tenant_cannot_edit_templates_retry_deliveries_or_acknowledge_incidents(): void
    {
        $other = $this->makeTenant();
        $template = CommunicationTemplate::factory()->create(['tenant_id' => $other->id]);
        $delivery = MessageDelivery::factory()->create(['tenant_id' => $other->id, 'status' => 'failed']);
        $router = Router::factory()->create(['tenant_id' => $other->id]);
        $incident = NotificationIncident::factory()->create(['tenant_id' => $other->id, 'router_id' => $router->id, 'status' => 'open']);
        $this->get(route('communications.edit', $template))->assertNotFound();
        $this->post(route('communications.retry', $delivery), ['action' => 'retry'])->assertNotFound();
        $this->post(route('communications.incident-action', $incident), ['action' => 'acknowledge'])->assertNotFound();
    }

    public function test_sms_segments_count_unicode_and_gsm_extensions(): void
    {
        $service = app(MessageTemplateService::class);
        $this->assertSame(2, $service->smsSegments(str_repeat('a', 161))['segments']);
        $this->assertSame(2, $service->smsSegments(str_repeat('س', 71))['segments']);
        $this->assertSame(2, $service->smsSegments(str_repeat('^', 81))['segments']);
    }
}
