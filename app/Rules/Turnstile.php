<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('services.turnstile.secret_key') || ! config('services.turnstile.site_key')) {
            $fail('Verification is temporarily unavailable. Please try again later.');

            return;
        }

        try {
            $response = Http::asForm()->connectTimeout(3)->timeout(10)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $value,
                ],
            );

            if (! $response->successful() || $response->json('success') !== true) {
                $fail('Verification failed or expired. Please complete the CAPTCHA again.');
            }
        } catch (ConnectionException) {
            $fail('Verification is temporarily unavailable. Please try again.');
        }
    }
}
