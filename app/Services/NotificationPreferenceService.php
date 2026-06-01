<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\Setting;
use App\Models\User;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Database\Eloquent\Model;

class NotificationPreferenceService
{
    /**
     * @return array{notifications_enabled: bool, in_app_enabled: bool, email_enabled: bool, sms_enabled: bool}
     */
    public function settingsFor(Model $notifiable): array
    {
        $preference = $this->preferenceFor($notifiable);

        return [
            'notifications_enabled' => $preference?->notifications_enabled ?? true,
            'in_app_enabled' => $preference?->in_app_enabled ?? true,
            'email_enabled' => $preference?->email_enabled ?? false,
            'sms_enabled' => $preference?->sms_enabled ?? false,
        ];
    }

    public function preferenceFor(Model $notifiable): ?NotificationPreference
    {
        $tenantId = $this->tenantIdFor($notifiable);

        if (! $tenantId) {
            return null;
        }

        return NotificationPreference::query()
            ->forTenant($tenantId)
            ->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', $notifiable->getKey())
            ->first();
    }

    /**
     * @param  array{notifications_enabled: bool, in_app_enabled: bool, email_enabled: bool, sms_enabled: bool}  $settings
     */
    public function updateFor(Model $notifiable, array $settings): NotificationPreference
    {
        $tenantId = $this->tenantIdFor($notifiable);

        if (! $tenantId) {
            abort(403, 'Tenant context is required.');
        }

        return NotificationPreference::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'notifiable_type' => $notifiable->getMorphClass(),
                'notifiable_id' => $notifiable->getKey(),
            ],
            $settings
        );
    }

    public function shouldDeliverInApp(Model $notifiable, string $eventKey, bool $critical = false): bool
    {
        if ($critical && $notifiable instanceof User) {
            return true;
        }

        $tenantId = $this->tenantIdFor($notifiable);

        if (! $tenantId) {
            return false;
        }

        $tenantSettings = $this->tenantSettings($tenantId);

        if (! ($tenantSettings['enabled'] ?? true) || ! ($tenantSettings['channels']['in_app'] ?? true)) {
            return false;
        }

        if (! ($tenantSettings['events'][$eventKey] ?? true)) {
            return false;
        }

        $settings = $this->settingsFor($notifiable);

        return $settings['notifications_enabled'] && $settings['in_app_enabled'];
    }

    /**
     * @return array{enabled: bool, channels: array{in_app: bool, email: bool, sms: bool}, events: array<string, bool>}
     */
    public function tenantSettings(string $tenantId): array
    {
        $settings = Setting::get('notifications.rules', [], $tenantId) ?? [];

        return [
            'enabled' => (bool) ($settings['enabled'] ?? true),
            'channels' => [
                'in_app' => (bool) ($settings['channels']['in_app'] ?? true),
                'email' => (bool) ($settings['channels']['email'] ?? false),
                'sms' => (bool) ($settings['channels']['sms'] ?? false),
            ],
            'events' => array_merge(NotificationEventRegistry::defaultRules(), $settings['events'] ?? []),
        ];
    }

    private function tenantIdFor(Model $notifiable): ?string
    {
        return $notifiable->tenant_id ?? tenant_id();
    }
}
