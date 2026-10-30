<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactInquiryRequest;
use App\Jobs\SendContactInquiryTelegram;
use App\Mail\ContactInquiryConfirmation;
use App\Mail\ContactInquiryReceived;
use App\Models\ContactInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(StoreContactInquiryRequest $request): RedirectResponse
    {
        $contactInquiry = ContactInquiry::query()->create([
            ...$request->safe()->only(['name', 'email', 'phone', 'company_name', 'subject', 'message']),
            'source_page' => 'contact',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        foreach (config('contact.recipients') as $recipient) {
            Mail::to($recipient)->queue(new ContactInquiryReceived($contactInquiry));
        }

        Mail::to($contactInquiry->email)->queue(new ContactInquiryConfirmation($contactInquiry));
        SendContactInquiryTelegram::dispatch($contactInquiry)->onQueue('notifications')->afterCommit();

        return redirect()
            ->route('contact.show')
            ->with('contact_success', 'Thanks for reaching out. Our team will get back to you shortly.');
    }
}
