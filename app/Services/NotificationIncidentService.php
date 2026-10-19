<?php

namespace App\Services;

use App\Models\CommunicationTemplate;
use App\Models\NotificationIncident;
use App\Models\Router;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class NotificationIncidentService
{
    public function policy(string $tenantId): array
    {
        return array_merge(['enabled' => false, 'failure_threshold' => 3, 'recovery_threshold' => 2, 'escalation_minutes' => [30, 120], 'quiet_start' => null, 'quiet_end' => null, 'maintenance_until' => null], Setting::get('notifications.incident_policy', [], $tenantId) ?? []);
    }

    public function observe(Router $router, bool $online): void
    {
        $policy = $this->policy($router->tenant_id);
        if (! $policy['enabled'] || ($policy['maintenance_until'] && now()->lt($policy['maintenance_until']))) {
            return;
        }
        Cache::lock('incident:'.$router->tenant_id.':'.$router->id, 30)->get(function () use ($router, $online, $policy): void {
            NotificationIncident::resolveConnection()->transaction(function () use ($router, $online, $policy): void {
                $incident = NotificationIncident::query()->where('tenant_id', $router->tenant_id)->where('router_id', $router->id)
                    ->whereIn('status', ['observing', 'open'])->lockForUpdate()->first();
                if ($incident?->observed_at && $incident->observed_at->gte(now()->subSeconds(30))) {
                    return;
                }
                if (! $incident) {
                    if ($online) {
                        return;
                    }
                    $incident = NotificationIncident::query()->create(['tenant_id' => $router->tenant_id, 'router_id' => $router->id]);
                }
                $incident->fill(['observed_at' => now(), 'failures' => $online ? 0 : $incident->failures + 1, 'successes' => $online ? $incident->successes + 1 : 0]);
                if ($incident->status === 'observing' && $incident->failures >= $policy['failure_threshold']) {
                    $incident->fill(['status' => 'open', 'opened_at' => now()]);
                    $incident->save();
                    $this->dispatch($incident, false);
                } elseif ($online && $incident->successes >= $policy['recovery_threshold']) {
                    $wasOpen = $incident->status === 'open';
                    $incident->fill(['status' => 'resolved', 'resolved_at' => now()])->save();
                    if ($wasOpen) {
                        $this->dispatch($incident, true);
                    }
                } else {
                    $incident->save();
                }
            });
        });
    }

    public function escalate(string $tenantId): void
    {
        $policy = $this->policy($tenantId);
        if (! $policy['enabled'] || ($policy['maintenance_until'] && now()->lt($policy['maintenance_until']))) {
            return;
        }
        NotificationIncident::query()->where('tenant_id', $tenantId)->where('status', 'open')->whereNull('acknowledged_at')->each(function (NotificationIncident $incident) use ($policy): void {
            $minutes = $policy['escalation_minutes'][$incident->stage] ?? null;
            if ($minutes === null || $incident->opened_at->copy()->addMinutes($minutes)->isFuture()) {
                return;
            }
            NotificationIncident::resolveConnection()->transaction(function () use ($incident): void {
                $claimed = NotificationIncident::query()->whereKey($incident->id)->where('stage', $incident->stage)->where('status', 'open')->whereNull('acknowledged_at')->update(['stage' => $incident->stage + 1]);
                if ($claimed) {
                    $this->dispatch($incident->fresh(), false);
                }
            });
        });
    }

    public function dispatch(NotificationIncident $incident, bool $recovery): void
    {
        $policy = $this->policy($incident->tenant_id);
        $eventKey = $recovery ? 'network.router_recovered' : 'network.router_degraded';
        $data = ['router_name' => $incident->router?->name ?? 'Router', 'incident_id' => $incident->id, 'stage' => $incident->stage, 'timestamp' => now()->toIso8601String(),
            'title' => $recovery ? 'Router recovered' : 'Router offline', 'body' => $recovery ? 'Connectivity has recovered.' : 'Connectivity failures have been confirmed.'];
        $availableAt = now();
        if ($policy['quiet_start'] && $policy['quiet_end']) {
            $local = now()->timezone(tenant()?->timezone ?? 'UTC');
            $start = $local->copy()->setTimeFromTimeString($policy['quiet_start']);
            $end = $local->copy()->setTimeFromTimeString($policy['quiet_end']);
            if ($end->lte($start)) {
                if ($local->gte($start)) {
                    $end->addDay();
                } else {
                    $start->subDay();
                }
            }
            if ($local->between($start, $end)) {
                $availableAt = $end->utc();
            }
        }
        $tenantSettings = app(NotificationPreferenceService::class)->tenantSettings($incident->tenant_id);
        if (! $tenantSettings['enabled'] || ! ($tenantSettings['events'][$eventKey] ?? true)) {
            return;
        }
        foreach (CommunicationTemplate::query()->where('tenant_id', $incident->tenant_id)->where('event_key', $eventKey)->where('enabled', true)->whereIn('recipient_type', ['admin', 'both'])->get() as $template) {
            foreach ($template->recipients ?? [] as $recipient) {
                if (($recipient['stage'] ?? 0) > $incident->stage) {
                    continue;
                }
                $channel = $recipient['channel'];
                if (! in_array($channel, $template->channels, true) || ! ($tenantSettings['channels'][$channel] ?? false)) {
                    continue;
                }
                app(CommunicationDeliveryService::class)->create($incident->tenant_id, implode(':', ['incident', $incident->id, $eventKey, $incident->stage, $channel, strtolower($recipient['destination'])]), [
                    'notification_incident_id' => $incident->id, 'event_key' => $eventKey, 'channel' => $channel, 'destination' => $recipient['destination'],
                    'content' => app(MessageTemplateService::class)->render($template->only(['subject', 'email_body', 'sms_body', 'sms_mode', 'lookup_template', 'lookup_tokens']), $data) + ['template_id' => $template->id, 'template_version' => $template->version],
                    'context' => ['outage' => ! $recovery, 'stage' => $incident->stage], 'available_at' => $availableAt, 'expires_at' => $availableAt->copy()->addHours(4),
                ]);
            }
        }
    }
}
