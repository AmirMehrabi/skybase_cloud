<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CloudSignupReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    /**
     * @param  array{business_name: string, owner_name: string, email: string, phone: ?string, country: ?string}  $signupDetails
     */
    public function __construct(public array $signupDetails)
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
            subject: 'New SkyBase Cloud signup',
            replyTo: [new Address($this->signupDetails['email'], $this->signupDetails['owner_name'])],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cloud-signup-received',
        );
    }
}
