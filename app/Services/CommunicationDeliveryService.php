<?php

namespace App\Services;

use App\Jobs\DeliverCommunication;
use App\Models\CommunicationTemplate;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MessageDelivery;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class CommunicationDeliveryService
{
    public function create(string $tenantId, string $identity, array $attributes): MessageDelivery
    {
        return MessageDelivery::query()->where('tenant_id', $tenantId)->firstOrCreate(
            ['tenant_id' => $tenantId, 'idempotency_key' => hash('sha256', $identity)],
            $attributes + ['available_at' => now()],
        )->refresh();
    }

    public function dispatchDue(string $tenantId): void
    {
        MessageDelivery::query()->where('tenant_id', $tenantId)->where('status', 'sending')
            ->where('claimed_at', '<', now()->subMinutes(5))
            ->update(['status' => 'unknown', 'last_error' => 'Worker stopped during sending. Verify provider acceptance before retrying.']);
        MessageDelivery::query()->where('tenant_id', $tenantId)->whereIn('status', ['pending', 'retrying'])
            ->where('available_at', '<=', now())->orderBy('id')->chunkById(100, function ($deliveries) use ($tenantId): void {
                foreach ($deliveries as $delivery) {
                    DeliverCommunication::dispatch($tenantId, $delivery->id)->onQueue($delivery->campaign_id ? 'campaigns' : 'notifications')->afterCommit();
                }
            });
    }

    public function skipReason(MessageDelivery $delivery): ?string
    {
        if ($delivery->expires_at?->isPast()) {
            return 'Message expired before sending.';
        }
        if ($delivery->campaign_id && ! in_array($delivery->campaign?->status, ['sending', 'scheduled'], true)) {
            return 'Campaign is paused or cancelled.';
        }
        if ($delivery->notification_incident_id && ($delivery->context['outage'] ?? false)) {
            $incident = $delivery->incident;
            if (! $incident || $incident->status !== 'open' || ($delivery->context['stage'] ?? 0) < $incident->stage || (($delivery->context['stage'] ?? 0) > 0 && $incident->acknowledged_at)) {
                return 'Incident recovered or escalation was acknowledged.';
            }
        }
        if (($delivery->context['invoice_unpaid'] ?? false) && isset($delivery->context['invoice_id'])) {
            $invoice = Invoice::query()->where('tenant_id', $delivery->tenant_id)->find($delivery->context['invoice_id']);
            if (! $invoice || in_array($invoice->status, ['paid', 'cancelled', 'void'], true)) {
                return 'Invoice is paid, cancelled or unavailable.';
            }
        }
        if ($delivery->event_key) {
            $rules = app(NotificationPreferenceService::class)->tenantSettings($delivery->tenant_id);
            if (! $rules['enabled'] || ! ($rules['channels'][$delivery->channel] ?? false) || ! ($rules['events'][$delivery->event_key] ?? true)) {
                return 'Notification rule or channel was disabled before sending.';
            }
            if ($templateId = $delivery->content['template_id'] ?? null) {
                if (! CommunicationTemplate::query()->where('tenant_id', $delivery->tenant_id)->whereKey($templateId)->where('enabled', true)->exists()) {
                    return 'Notification template was disabled before sending.';
                }
            }
        }
        if ($delivery->recipient_id && in_array($delivery->recipient_type, ['customer', 'user'], true)) {
            $class = $delivery->recipient_type === 'customer' ? Customer::class : User::class;
            $recipient = $class::query()->where('tenant_id', $delivery->tenant_id)->find($delivery->recipient_id);
            if (! $recipient) {
                return 'Recipient is unavailable.';
            }
            if (! app(NotificationPreferenceService::class)->shouldDeliverExternal($recipient, $delivery->event_key, $delivery->channel, (bool) $delivery->campaign_id)) {
                return 'Recipient or tenant has disabled this notification channel.';
            }
        }
        if ($delivery->channel === 'email' && ! filter_var($delivery->destination, FILTER_VALIDATE_EMAIL)) {
            return 'Missing or invalid email address.';
        }
        if ($delivery->channel === 'sms' && ! preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/\s+/', '', $delivery->destination ?? ''))) {
            return 'Missing or invalid phone number (use country code).';
        }

        return null;
    }

    public function deliver(MessageDelivery $delivery): void
    {
        if (! in_array($delivery->status, ['pending', 'retrying'], true) || $delivery->available_at?->isFuture()) {
            return;
        }
        if ($delivery->campaign?->status === 'paused') {
            return;
        }
        if ($reason = $this->skipReason($delivery)) {
            $delivery->update(['status' => 'skipped', 'last_error' => $reason]);

            return;
        }
        $settings = app(SmsGatewayService::class)->settings($delivery->tenant_id);
        $key = 'communication:'.$delivery->tenant_id.':'.$delivery->channel.':'.($delivery->provider ?? $settings['provider']);
        if (RateLimiter::tooManyAttempts($key, $settings['rate_per_minute'])) {
            $delivery->update(['available_at' => now()->addSeconds(60)]);

            return;
        }
        $claimed = MessageDelivery::query()->where('tenant_id', $delivery->tenant_id)->whereKey($delivery->id)
            ->whereIn('status', ['pending', 'retrying'])->where('available_at', '<=', now())->where('attempts', $delivery->attempts)->update(['status' => 'sending', 'claimed_at' => now(), 'attempts' => $delivery->attempts + 1]);
        if (! $claimed) {
            return;
        }
        RateLimiter::hit($key, 60);
        $delivery->refresh();
        try {
            $result = $delivery->channel === 'sms'
                ? app(SmsGatewayService::class)->send($delivery->tenant_id, preg_replace('/\s+/', '', $delivery->destination), $delivery->content, $delivery->provider)
                : app(TenantMailService::class)->send($delivery->tenant_id, $delivery->destination, $delivery->content);
            $delivery->update(['status' => $result['status'], 'provider' => $result['provider'], 'provider_message_id' => $result['id'], 'accepted_at' => now(), 'last_error' => null]);
        } catch (Throwable $exception) {
            $code = (int) $exception->getCode();
            $temporary = $code === 429 || $code === 503;
            $unknown = $exception instanceof ConnectionException || $code === 0 || ($code >= 500 && $code !== 503);
            $status = $unknown ? 'unknown' : ($temporary && $delivery->attempts < 3 ? 'retrying' : 'failed');
            $delivery->update(['status' => $status, 'last_error' => $unknown ? 'Delivery outcome is uncertain. Check the provider before retrying.' : 'Gateway rejected delivery (code '.$code.'). Check configuration or recipient.',
                'available_at' => now()->addSeconds($delivery->attempts === 1 ? 60 : 300)]);
        }
    }

    public function refreshReceipts(string $tenantId): void
    {
        MessageDelivery::query()->where('tenant_id', $tenantId)->where('status', 'accepted')->where('channel', 'sms')
            ->whereIn('provider', ['twilio', 'kavenegar'])->whereNotNull('provider_message_id')->where('accepted_at', '>', now()->subDays(2))
            ->where(fn ($query) => $query->whereNull('receipt_checked_at')->orWhere('receipt_checked_at', '<', now()->subMinutes(5)))
            ->limit(50)->get()->each(function (MessageDelivery $delivery) use ($tenantId): void {
                try {
                    $status = app(SmsGatewayService::class)->receipt($tenantId, $delivery->provider, $delivery->provider_message_id);
                    $delivery->update(['receipt_checked_at' => now()] + ($status ? ['status' => $status, 'delivered_at' => $status === 'delivered' ? now() : null, 'last_error' => $status === 'failed' ? 'Provider reports delivery failure.' : null] : []));
                } catch (Throwable) {
                    $delivery->update(['receipt_checked_at' => now()]);
                }
            });
    }
}
