<?php

namespace App\Services;

use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OperationalIncidentNotifier
{
    public function __construct(private TenantNotificationService $notifications) {}

    public function observe(Model $subject, string $check, bool $healthy, string $title, string $body, string $url): void
    {
        $key = "operational-incident:{$subject->tenant_id}:{$check}:{$subject->getKey()}";

        Cache::lock($key.':lock', 30)->get(function () use ($subject, $healthy, $title, $body, $url, $key): void {
            $incident = Cache::get($key);
            if ($healthy === ($incident === null)) {
                return;
            }

            $identity = $incident ?? (string) Str::uuid();
            app(TenantContext::class)->run((string) $subject->tenant_id, function () use ($subject, $healthy, $title, $body, $url, $identity): void {
                $this->notifications->notifyAdmins((string) $subject->tenant_id, NotificationEventRegistry::OPERATIONAL_FAILURE, [
                    'title' => $title,
                    'body' => $body,
                    'action_url' => $url,
                    'event_identity' => $identity.($healthy ? ':resolved' : ':opened'),
                    ...($healthy ? ['severity' => 'info'] : []),
                ], $subject);
            });

            if ($healthy) {
                Cache::forget($key);
            } else {
                Cache::forever($key, $identity);
            }
        });
    }
}
