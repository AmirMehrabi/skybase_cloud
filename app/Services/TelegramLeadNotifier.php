<?php

namespace App\Services;

use App\Models\ContactInquiry;
use App\Models\DemoRequest;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramLeadNotifier
{
    public function __construct(private Repository $config) {}

    public function notifyContactInquiry(ContactInquiry $contactInquiry): void
    {
        $this->sendMessage(implode("\n", [
            'New contact inquiry',
            'Name: '.$contactInquiry->name,
            'Email: '.$contactInquiry->email,
            'Phone: '.($contactInquiry->phone ?: 'Not provided'),
            'Company: '.($contactInquiry->company_name ?: 'Not provided'),
            'Subject: '.$contactInquiry->subject,
            'Message: '.$contactInquiry->message,
        ]));
    }

    public function notifyDemoRequest(DemoRequest $demoRequest): void
    {
        $this->sendMessage(implode("\n", [
            'New demo request',
            'Plan: '.$demoRequest->requested_plan,
            'Business: '.$demoRequest->business_name,
            'Contact: '.$demoRequest->contact_name,
            'Email: '.$demoRequest->email,
            'Phone: '.($demoRequest->phone ?: 'Not provided'),
            'Country: '.$demoRequest->country,
            'Website: '.($demoRequest->company_website ?: 'Not provided'),
            'Customers: '.number_format($demoRequest->customer_count),
            'Current system: '.($demoRequest->current_system ?: 'Not provided'),
            'Timeline: '.($demoRequest->deployment_timeline ?: 'Not provided'),
            'Notes: '.($demoRequest->message ?: 'Not provided'),
        ]));
    }

    private function sendMessage(string $message): void
    {
        $botToken = (string) $this->config->get('services.telegram.bot_token', '');
        $chatId = (string) $this->config->get('services.telegram.leads_chat_id', '');

        if ($botToken === '' || $chatId === '') {
            return;
        }

        try {
            Http::asForm()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                ])
                ->throw();
        } catch (Throwable $throwable) {
            Log::warning('Telegram lead notification failed.', [
                'message' => $throwable->getMessage(),
            ]);
        }
    }
}
