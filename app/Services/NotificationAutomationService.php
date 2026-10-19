<?php

namespace App\Services;

use App\Models\CommunicationTemplate;
use App\Models\Customer;
use App\Models\Invoice;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class NotificationAutomationService
{
    public function ensureDefaults(string $tenantId): void
    {
        foreach (NotificationEventRegistry::events() as $key => $event) {
            $recipientType = $event['roles'] === [] ? 'customer' : 'admin';
            CommunicationTemplate::query()->where('tenant_id', $tenantId)->firstOrCreate(['tenant_id' => $tenantId, 'event_key' => $key, 'recipient_type' => $recipientType], [
                'name' => $event['label'], 'enabled' => false, 'channels' => ['email', 'sms'], 'subject' => '{{title}}', 'email_body' => '{{body}}', 'sms_body' => '{{body}}', 'recipients' => [],
            ]);
        }
    }

    public function send(Model $recipient, string $eventKey, array $payload, ?Model $related = null): void
    {
        $tenantId = (string) $recipient->tenant_id;
        $kind = $recipient instanceof Customer ? 'customer' : 'admin';
        $identity = $payload['event_identity'] ?? implode(':', [$related?->getMorphClass() ?? 'event', $related?->getKey() ?? hash('sha256', json_encode($payload)), $payload['related_id'] ?? '', (str_starts_with($eventKey, 'subscription.') || $eventKey === 'network.router_degraded') ? ($related?->updated_at?->format('U.u') ?? '') : '']);
        $data = array_merge(['customer_name' => $recipient instanceof Customer ? $recipient->full_name : $recipient->name, 'first_name' => $recipient->first_name, 'email' => $recipient->email, 'phone' => $recipient->phone, 'timestamp' => now()->toIso8601String()], array_filter($payload, fn ($value): bool => is_scalar($value) || $value === null));
        if ($recipient instanceof Customer) {
            $recipient->loadMissing('subscriptions');
            $data = array_merge(app(MessageTemplateService::class)->customerData($recipient), $data);
        }
        if ($related instanceof Invoice) {
            $data += ['invoice_id' => $related->id, 'invoice_amount' => $related->total, 'due_date' => $related->due_date?->toDateString()];
        }
        CommunicationTemplate::query()->where('tenant_id', $tenantId)->where('event_key', $eventKey)->where('enabled', true)
            ->whereIn('recipient_type', [$kind, 'both'])->get()->each(function (CommunicationTemplate $template) use ($recipient, $eventKey, $identity, $tenantId, $payload, $data, $kind): void {
                try {
                    $content = app(MessageTemplateService::class)->render($template->only(['subject', 'email_body', 'sms_body', 'sms_mode', 'lookup_template', 'lookup_tokens']), $data);
                    $error = null;
                } catch (ValidationException) {
                    $content = [];
                    $error = 'Template token values are incompatible with the Lookup template.';
                }
                foreach ($template->channels as $channel) {
                    if (! app(NotificationPreferenceService::class)->shouldDeliverExternal($recipient, $eventKey, $channel)) {
                        continue;
                    }
                    app(CommunicationDeliveryService::class)->create($tenantId, implode(':', ['event', $eventKey, $identity, $template->id, $kind, $recipient->id, $channel]), [
                        'event_key' => $eventKey, 'recipient_type' => $kind === 'customer' ? 'customer' : 'user', 'recipient_id' => $recipient->id,
                        'channel' => $channel, 'destination' => $channel === 'email' ? $recipient->email : ($recipient->mobile ?: $recipient->phone),
                        'content' => $content + ['template_id' => $template->id, 'template_version' => $template->version], 'status' => $error ? 'failed' : 'pending', 'last_error' => $error, 'context' => Arr::only($payload, ['invoice_id', 'invoice_unpaid']), 'expires_at' => now()->addDay(),
                    ]);
                }
            });
    }
}
