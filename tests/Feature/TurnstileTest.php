<?php

namespace Tests\Feature;

use App\Rules\Turnstile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile.site_key' => 'test-site', 'services.turnstile.secret_key' => 'test-secret']);
    }

    public function test_verified_token_is_accepted_and_sent_to_cloudflare(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->assertTrue($this->validator()->passes());
        Http::assertSent(fn ($request): bool => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'test-secret' && $request['response'] === 'test-token');
    }

    public function test_invalid_or_expired_token_is_rejected(): void
    {
        Http::fake(['*' => Http::response(['success' => false, 'error-codes' => ['timeout-or-duplicate']])]);

        $this->assertTrue($this->validator()->fails());
    }

    public function test_provider_error_is_rejected(): void
    {
        Http::fake(['*' => Http::response([], 503)]);

        $this->assertTrue($this->validator()->fails());
    }

    public function test_connection_failure_is_rejected(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->assertTrue($this->validator()->fails());
    }

    public function test_missing_configuration_is_rejected_without_a_network_call(): void
    {
        config(['services.turnstile.secret_key' => null]);
        Http::fake();

        $this->assertTrue($this->validator()->fails());
        Http::assertNothingSent();
    }

    #[DataProvider('submissionRoutes')]
    public function test_both_endpoints_require_a_captcha(string $route, string $bag): void
    {
        Http::fake();

        $this->post(route($route), [])
            ->assertSessionHasErrors(['cf-turnstile-response'], errorBag: $bag);
        Http::assertNothingSent();
    }

    public static function submissionRoutes(): array
    {
        return [
            ['contact.store', 'contactInquiry'],
            ['demo-requests.store', 'demoRequest'],
        ];
    }

    public function test_all_lead_forms_render_the_widget_and_allow_its_frame(): void
    {
        foreach (['home', 'contact.show', 'pricing'] as $route) {
            $response = $this->get(route($route));
            $response->assertOk()->assertSee('class="cf-turnstile"', false)
                ->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js', false)
                ->assertDontSee('test-secret');
            $policy = $response->headers->get('Content-Security-Policy')
                ?? $response->headers->get('Content-Security-Policy-Report-Only');
            $this->assertStringContainsString("frame-src 'self' https://challenges.cloudflare.com", $policy);
        }
    }

    private function validator(): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make(['token' => 'test-token'], ['token' => [new Turnstile]]);
    }
}
