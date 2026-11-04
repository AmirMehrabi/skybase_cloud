<?php

namespace App\Listeners;

use App\Jobs\SendCloudSignupTelegram;
use App\Mail\CloudSignupReceived;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CloudSignupVerified
{
    public function handle(Verified $event): void
    {
        $user = $event->user;
        if (! $user->email_verification_required) {
            return;
        }

        $tenant = $user->tenant;
        if (! $tenant) {
            return;
        }

        $signupDetails = [
            'business_name' => $tenant->company_name,
            'owner_name' => $user->name,
            'email' => $user->email,
            'phone' => $tenant->phone,
            'country' => $tenant->country,
        ];

        foreach (config('contact.recipients') as $recipient) {
            try {
                Mail::to($recipient)->queue(new CloudSignupReceived($signupDetails));
            } catch (Throwable $throwable) {
                Log::warning('Cloud signup email notification could not be queued.', ['exception_type' => $throwable::class]);
            }
        }

        try {
            SendCloudSignupTelegram::dispatch($signupDetails)->onQueue('notifications');
        } catch (Throwable $throwable) {
            Log::warning('Cloud signup Telegram notification could not be queued.', ['exception_type' => $throwable::class]);
        }
    }
}
