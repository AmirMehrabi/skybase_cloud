<?php

namespace App\Services;

use App\Models\Setting;
use RuntimeException;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class TenantMailService
{
    public function transport(array $settings): EsmtpTransport
    {
        $host = (string) ($settings['host'] ?? '');
        $port = (int) ($settings['port'] ?? 587);
        $resolved = app(OutboundConnectionPolicy::class)->resolve($host, $port, configuredDestination: true);
        $encryption = $settings['encryption'] ?? 'tls';
        $transport = new EsmtpTransport($resolved, $port, $encryption === 'ssl');
        $transport->setAutoTls($encryption !== 'none');
        $transport->setRequireTls($encryption === 'tls');
        $transport->setUsername((string) ($settings['username'] ?? ''));
        $transport->setPassword((string) ($settings['password'] ?? ''));
        $transport->getStream()->setTimeout(10);
        $transport->getStream()->setStreamOptions(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);

        return $transport;
    }

    public function send(string $tenantId, string $destination, array $content): array
    {
        $settings = Setting::get('email.outgoing', [], $tenantId);
        if (! ($settings['active'] ?? false) || empty($settings['from_email'])) {
            throw new RuntimeException('Outgoing email is inactive or missing a sender address.', 422);
        }
        $email = (new Email)->from(new Address($settings['from_email'], $settings['from_name'] ?? 'SkyBase'))
            ->to($destination)->subject($content['subject'] ?? 'SkyBase')->text($content['email_body'] ?? '');
        $transport = $this->transport($settings);
        try {
            $sent = $transport->send($email);

            return ['provider' => 'smtp', 'id' => $sent?->getMessageId(), 'status' => 'accepted'];
        } finally {
            $transport->stop();
        }
    }
}
