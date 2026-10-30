<?php

namespace App\Jobs;

use App\Models\ContactInquiry;
use App\Services\TelegramLeadNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendContactInquiryTelegram implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 40;

    public function __construct(public ContactInquiry $inquiry) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 300, 600];
    }

    public function handle(TelegramLeadNotifier $notifier): void
    {
        $notifier->notifyContactInquiry($this->inquiry);
    }
}
