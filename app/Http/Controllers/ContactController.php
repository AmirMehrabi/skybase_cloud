<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactInquiryRequest;
use App\Models\ContactInquiry;
use App\Services\TelegramLeadNotifier;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function store(StoreContactInquiryRequest $request, TelegramLeadNotifier $telegramLeadNotifier): RedirectResponse
    {
        $contactInquiry = ContactInquiry::query()->create([
            ...$request->validated(),
            'source_page' => 'contact',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $telegramLeadNotifier->notifyContactInquiry($contactInquiry);

        return redirect()
            ->route('contact.show')
            ->with('contact_success', 'Thanks for reaching out. Our team will get back to you shortly.');
    }
}
