<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRequiredEmailIsVerified;
use App\Jobs\SendCloudSignupTelegram;
use App\Mail\CloudSignupReceived;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CloudSignupAndEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.cloud.enabled' => true,
            'contact.recipients' => ['alerts@skybase.test', 'founder@skybase.test'],
        ]);
        Mail::fake();
        Notification::fake();
        Bus::fake([SendCloudSignupTelegram::class]);
    }

    public function test_cloud_signup_requires_email_verification_and_notifies_the_team(): void
    {
        $response = $this->post(route('auth.register.store'), $this->registrationPayload());

        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHas('success');

        $user = auth()->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertTrue($user->email_verification_required);
        $this->assertAuthenticatedAs($user);

        Notification::assertSentTo($user, VerifyEmail::class);
        Mail::assertQueuedCount(2);
        foreach (config('contact.recipients') as $recipient) {
            Mail::assertQueued(CloudSignupReceived::class, fn (CloudSignupReceived $mail): bool => $mail->hasTo($recipient)
                && $mail->signupDetails['business_name'] === 'Clear Sky Fiber'
                && $mail->signupDetails['phone'] === '+33 7 58 35 14 73');
        }
        Bus::assertDispatched(SendCloudSignupTelegram::class, fn (SendCloudSignupTelegram $job): bool => $job->signupDetails['email'] === 'jane@example.com'
            && $job->signupDetails['business_name'] === 'Clear Sky Fiber'
            && $job->queue === 'notifications');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'email_verified_at' => null,
            'email_verification_required' => true,
        ]);
    }

    public function test_unverified_cloud_signup_is_redirected_to_the_verification_notice(): void
    {
        $this->post(route('auth.register.store'), $this->registrationPayload());

        $this->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Check your inbox')
            ->assertSee(route('verification.send'), false);
    }

    public function test_signed_verification_link_marks_the_cloud_signup_verified(): void
    {
        $this->post(route('auth.register.store'), $this->registrationPayload());
        $user = auth()->user();
        $this->assertInstanceOf(User::class, $user);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'email_verified_at' => $user->email_verified_at,
        ]);
    }

    public function test_cloud_signup_can_request_another_verification_email(): void
    {
        $this->post(route('auth.register.store'), $this->registrationPayload());
        $user = auth()->user();
        $this->assertInstanceOf(User::class, $user);

        $this->get(route('verification.notice'));
        $this->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('success');

        Notification::assertSentToTimes($user, VerifyEmail::class, 2);
    }

    public function test_existing_accounts_that_do_not_require_verification_are_not_blocked(): void
    {
        $user = new User(['email_verification_required' => false]);
        $request = request()->create('/dashboard');
        $request->setUserResolver(fn (): User => $user);

        $response = app(EnsureRequiredEmailIsVerified::class)
            ->handle($request, fn (): Response => response('Allowed'));

        $this->assertSame('Allowed', $response->getContent());
    }

    /** @return array<string, string> */
    private function registrationPayload(): array
    {
        return [
            'company_name' => 'Clear Sky Fiber',
            'owner_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'A-strong-pass-2026',
            'password_confirmation' => 'A-strong-pass-2026',
            'phone' => '+33 7 58 35 14 73',
            'country' => 'France',
            'timezone' => 'Europe/Paris',
        ];
    }
}
