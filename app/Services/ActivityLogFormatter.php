<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ActivityLogFormatter
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function forSubject(Model $subject, ?string $tenantId, int $limit = 20): Collection
    {
        return $this->formatCollection(
            Activity::query()
                ->forSubject($subject)
                ->forTenant($tenantId)
                ->with('causer')
                ->latest()
                ->limit($limit)
                ->get()
        );
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, array<string, mixed>>
     */
    public function formatCollection(Collection $activities): Collection
    {
        return $activities->map(fn (Activity $activity): array => $this->format($activity));
    }

    /**
     * @return array<string, mixed>
     */
    public function format(Activity $activity): array
    {
        $event = $activity->event ?? 'logged';
        $modelName = class_basename($activity->subject_type ?? 'Activity');
        $causerName = $activity->causer?->name ?? 'System';
        $changedAttributes = $this->changedAttributes($activity);

        return [
            'title' => Str::headline($modelName.' '.$event),
            'action' => Str::headline($modelName.' '.$event),
            'description' => $this->description($event, $causerName, $changedAttributes),
            'user' => $causerName,
            'time' => $activity->created_at?->diffForHumans() ?? '',
            'timestamp' => $activity->created_at?->format('M d, Y H:i') ?? '',
            'event' => $event,
            'changes' => $changedAttributes,
            'iconColor' => $this->iconColor($event),
            'icon' => $this->icon($event),
        ];
    }

    /**
     * @return list<string>
     */
    private function changedAttributes(Activity $activity): array
    {
        $changes = $activity->attribute_changes;
        $attributes = collect($changes?->get('attributes', []) ?? []);
        $old = collect($changes?->get('old', []) ?? []);

        return $attributes
            ->keys()
            ->merge($old->keys())
            ->unique()
            ->reject(fn (string $attribute): bool => in_array($attribute, ['password', 'remember_token', 'pppoe_password'], true))
            ->map(fn (string $attribute): string => Str::headline($attribute))
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $changedAttributes
     */
    private function description(string $event, string $causerName, array $changedAttributes): string
    {
        if ($event === 'updated' && $changedAttributes !== []) {
            return 'Updated by '.$causerName.': '.implode(', ', $changedAttributes);
        }

        return Str::headline($event).' by '.$causerName;
    }

    private function iconColor(string $event): string
    {
        return match ($event) {
            'created' => 'bg-green-100 text-green-600',
            'updated' => 'bg-blue-100 text-blue-600',
            'deleted' => 'bg-red-100 text-red-600',
            'restored' => 'bg-purple-100 text-purple-600',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    private function icon(string $event): string
    {
        return match ($event) {
            'created' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>',
            'updated' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>',
            'deleted' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6"></path>',
            default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path>',
        };
    }
}
