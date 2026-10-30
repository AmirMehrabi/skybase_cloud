<?php

namespace App\Mail;

use App\Models\ContactInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiryConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public ContactInquiry $inquiry)
    {
        $this->onQueue('notifications');
        $this->afterCommit();
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 300, 600];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Abbie Barlowe at SkyBase'),
            subject: 'Thanks for writing to SkyBase',
            replyTo: [new Address(config('mail.from.address'), 'Abbie Barlowe at SkyBase')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-inquiry-confirmation',
            text: 'emails.contact-inquiry-confirmation-text',
        );
    }
}
