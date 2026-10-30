<?php

namespace Tests\Feature;

use App\Jobs\SendContactInquiryTelegram;
use App\Mail\ContactInquiryConfirmation;
use App\Mail\ContactInquiryReceived;
use App\Models\ContactInquiry;
use App\Services\TelegramLeadNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ContactInquiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Bus::fake([SendContactInquiryTelegram::class]);
        config(['contact.recipients' => ['first@example.com', 'second@example.com']]);
    }

    public function test_valid_inquiry_queues_separate_recipient_emails_confirmation_and_telegram(): void
    {
        $this->post(route('contact.store'), $this->payload())->assertSessionHasNoErrors()->assertSessionHas('contact_success');
        $this->assertDatabaseCount('contact_inquiries', 1);
        foreach (config('contact.recipients') as $recipient) {
            Mail::assertQueued(ContactInquiryReceived::class, fn ($mail): bool => $mail->hasTo($recipient) && $mail->queue === 'notifications');
        }
        Mail::assertQueued(ContactInquiryConfirmation::class, fn ($mail): bool => $mail->hasTo('customer@example.com'));
        Mail::assertQueuedCount(3);
        Bus::assertDispatched(SendContactInquiryTelegram::class, fn ($job): bool => $job->inquiry->email === 'customer@example.com' && $job->queue === 'notifications');
    }

    public function test_honeypot_rejects_bots_without_saving_or_notifying(): void
    {
        $this->post(route('contact.store'), [...$this->payload(), 'website' => 'https://spam.example'])
            ->assertSessionHasErrors(['website'], errorBag: 'contactInquiry');
        $this->assertRejected();
    }

    public function test_missing_form_token_is_rejected(): void
    {
        $payload = $this->payload();
        unset($payload['form_token']);
        $this->post(route('contact.store'), $payload)->assertSessionHasErrors(['form_token'], errorBag: 'contactInquiry');
        $this->assertRejected();
    }

    public function test_tampered_form_token_is_rejected(): void
    {
        $this->post(route('contact.store'), [...$this->payload(), 'form_token' => 'forged'])
            ->assertSessionHasErrors(['form_token'], errorBag: 'contactInquiry');
        $this->assertRejected();
    }

    public function test_instant_submissions_are_rejected(): void
    {
        $this->post(route('contact.store'), $this->payload(0))->assertSessionHasErrors(['form_token'], errorBag: 'contactInquiry');
        $this->assertRejected();
    }

    public function test_expired_form_tokens_are_rejected(): void
    {
        $this->post(route('contact.store'), $this->payload(7201))->assertSessionHasErrors(['form_token'], errorBag: 'contactInquiry');
        $this->assertRejected();
    }

    public function test_token_from_another_session_is_rejected(): void
    {
        $payload = $this->payload();
        $payload['form_token'] = Crypt::encryptString(hash('sha256', 'other-session').'|'.now()->subSeconds(4)->timestamp);
        $this->post(route('contact.store'), $payload)->assertSessionHasErrors(['form_token'], errorBag: 'contactInquiry');
        $this->assertRejected();
    }

    public function test_invalid_customer_email_is_rejected(): void
    {
        $this->post(route('contact.store'), [...$this->payload(), 'email' => 'invalid'])
            ->assertSessionHasErrors(['email'], errorBag: 'contactInquiry');
        $this->assertRejected();
    }

    public function test_phone_number_is_required_for_contact_inquiries(): void
    {
        $payload = $this->payload();
        unset($payload['phone']);

        $this->post(route('contact.store'), $payload)
            ->assertSessionHasErrors(['phone'], errorBag: 'contactInquiry');

        $this->assertRejected();
    }

    public function test_ip_submission_limit_is_always_enforced(): void
    {
        config(['security.enforce_rate_limits' => false]);
        $payload = $this->payload();
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('contact.store'), $payload)->assertRedirect();
        }
        $this->post(route('contact.store'), $payload)->assertStatus(429);
        $this->assertDatabaseCount('contact_inquiries', 3);
    }

    public function test_email_limit_applies_across_ip_addresses(): void
    {
        $payload = $this->payload();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.($attempt + 1)])
                ->post(route('contact.store'), $payload)->assertRedirect();
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->post(route('contact.store'), $payload)->assertStatus(429);
        $this->assertDatabaseCount('contact_inquiries', 5);
    }

    public function test_telegram_delivery_sends_full_long_messages_in_safe_chunks(): void
    {
        config(['services.telegram.bot_token' => 'test-token', 'services.telegram.leads_chat_id' => '@test-channel']);
        Http::fake(['*' => Http::response(['ok' => true])]);
        $inquiry = ContactInquiry::factory()->create(['message' => str_repeat('آ', 5000)]);
        (new SendContactInquiryTelegram($inquiry))->handle(app(TelegramLeadNotifier::class));
        Http::assertSentCount(2);
        $text = '';
        foreach (Http::recorded() as [$request]) {
            $this->assertLessThanOrEqual(4000, mb_strlen($request['text']));
            $this->assertSame('@test-channel', $request['chat_id']);
            $text .= $request['text'];
        }
        $this->assertStringContainsString($inquiry->message, $text);
    }

    public function test_telegram_failure_is_retryable_and_does_not_expose_token(): void
    {
        config(['services.telegram.bot_token' => 'secret-test-token', 'services.telegram.leads_chat_id' => '@test-channel']);
        Http::fake(['*' => Http::response(['ok' => false], 403)]);
        try {
            (new SendContactInquiryTelegram(ContactInquiry::factory()->create()))->handle(app(TelegramLeadNotifier::class));
            $this->fail('Expected a retryable exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString('secret-test-token', (string) $exception);
        }
    }

    public function test_email_content_escapes_customer_input_and_supports_replies(): void
    {
        $inquiry = ContactInquiry::factory()->create(['name' => '<script>alert(1)</script>']);
        $notification = new ContactInquiryReceived($inquiry);
        $this->assertStringContainsString('&lt;script&gt;', $notification->render());
        $this->assertStringNotContainsString('<script>', $notification->render());
        $this->assertSame($inquiry->email, $notification->envelope()->replyTo[0]->address);
        $confirmation = new ContactInquiryConfirmation($inquiry);
        $this->assertStringContainsString('Your message is with our team.', $confirmation->render());
        $this->assertStringNotContainsString($inquiry->message, $confirmation->render());
    }

    public function test_confirmation_is_personalized_from_abbie_with_html_and_plain_text(): void
    {
        config(['mail.from.address' => 'info@skybase.app']);
        $inquiry = ContactInquiry::factory()->create(['name' => '  Jane   Doe  ']);
        $confirmation = new ContactInquiryConfirmation($inquiry);
        $envelope = $confirmation->envelope();

        $this->assertSame('Thanks for writing to SkyBase', $envelope->subject);
        $this->assertSame('info@skybase.app', $envelope->from->address);
        $this->assertSame('Abbie Barlowe at SkyBase', $envelope->from->name);
        $this->assertSame('info@skybase.app', $envelope->replyTo[0]->address);
        $confirmation->assertSeeInHtml('Hi Jane,');
        $confirmation->assertDontSeeInHtml('Jane Doe');
        $confirmation->assertSeeInHtml('I’m Abbie, CTO at SkyBase. Thanks for getting in touch.');
        $confirmation->assertSeeInHtml('Abbie Barlowe');
        $confirmation->assertSeeInText('Hi Jane,');
        $confirmation->assertSeeInText('No need to fill out the form again.');
        $confirmation->assertSeeInText('CTO, SkyBase');
    }

    /** @return array<string, string> */
    private function payload(int $age = 4): array
    {
        $this->get(route('contact.show'))->assertOk()->assertSee('name="form_token"', false);
        $this->withCookie(config('session.cookie'), session()->getId());

        return [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '+33 7 58 35 14 73',
            'subject' => 'Platform question',
            'message' => 'Please tell me more about SkyBase.',
            'form_token' => Crypt::encryptString(hash('sha256', session()->getId()).'|'.now()->subSeconds($age)->timestamp),
        ];
    }

    private function assertRejected(): void
    {
        $this->assertDatabaseCount('contact_inquiries', 0);
        Mail::assertNothingQueued();
        Bus::assertNotDispatched(SendContactInquiryTelegram::class);
    }
}
