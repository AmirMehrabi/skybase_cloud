<?php

namespace App\Jobs;

use App\Services\TelegramLeadNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCloudSignupTelegram implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 40;

    /**
     * @param  array{business_name: string, owner_name: string, email: string, phone: ?string, country: ?string}  $signupDetails
     */
    public function __construct(public array $signupDetails)
    {
        $this->onQueue('notifications');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 300, 600];
    }

    public function handle(TelegramLeadNotifier $notifier): void
    {
        $notifier->notifyCloudSignup($this->signupDetails);
    }
}
