<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CampaignService
{
    public function audience(array $filters): Builder
    {
        $query = Customer::query()->filter($filters);
        if (! empty($filters['customer_ids'])) {
            $query->whereIn('id', $filters['customer_ids']);
        }
        if (array_filter(Arr::only($filters, ['router_id', 'plan_id', 'site', 'subscription_status', 'connection_status']))) {
            $query->whereHas('subscriptions', function (Builder $subscription) use ($filters): void {
                foreach (['router_id', 'plan_id', 'site', 'connection_status'] as $field) {
                    if (filled($filters[$field] ?? null)) {
                        $subscription->where($field, $filters[$field]);
                    }
                }
                if (filled($filters['subscription_status'] ?? null)) {
                    $subscription->where('status', $filters['subscription_status']);
                }
            });
        }

        return $query;
    }

    public function preview(array $filters, array $channels, array $content): array
    {
        $stats = ['selected' => 0, 'eligible' => 0, 'skipped' => 0, 'email' => 0, 'sms' => 0, 'samples' => []];
        $this->audience($filters)->with('subscriptions')->chunkById(100, function ($customers) use (&$stats, $channels, $content): void {
            foreach ($customers as $customer) {
                $stats['selected']++;
                foreach ($channels as $channel) {
                    $destination = $channel === 'email' ? $customer->email : ($customer->mobile ?: $customer->phone);
                    $valid = $channel === 'email' ? filter_var($destination, FILTER_VALIDATE_EMAIL) : preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/\s+/', '', $destination ?? ''));
                    $allowed = app(NotificationPreferenceService::class)->shouldDeliverExternal($customer, null, $channel, true);
                    if ($valid && $allowed) {
                        $stats['eligible']++;
                        $stats[$channel]++;
                    } else {
                        $stats['skipped']++;
                    }
                }
                if (count($stats['samples']) < 3) {
                    $rendered = app(MessageTemplateService::class)->render($content, app(MessageTemplateService::class)->customerData($customer));
                    $stats['samples'][] = ['name' => $customer->full_name, 'content' => $rendered, 'sms' => app(MessageTemplateService::class)->smsSegments($rendered['sms_body'] ?? '')];
                }
            }
        });

        return $stats;
    }

    public function materialize(Campaign $campaign): void
    {
        $guard = auth('web');
        $previous = $guard->hasUser() ? $guard->user() : null;
        $creator = User::query()->where('tenant_id', $campaign->tenant_id)->find($campaign->created_by);
        if ($campaign->created_by && (! $creator || $creator->status !== 'active' || ! $creator->hasPermission('campaigns.actions'))) {
            $campaign->update(['status' => 'cancelled']);

            return;
        }
        if ($creator) {
            $guard->setUser($creator);
        }
        try {
            $this->materializeAudience($campaign);
        } finally {
            $guard->forgetUser();
            if ($previous) {
                $guard->setUser($previous);
            }
        }
    }

    private function materializeAudience(Campaign $campaign): void
    {
        Campaign::resolveConnection()->transaction(function () use ($campaign): void {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            if ($campaign->materialized_at || $campaign->status !== 'scheduled' || $campaign->scheduled_at?->isFuture()) {
                return;
            }
            $campaign->update(['status' => 'sending']);
            $query = $campaign->audience_mode === 'dynamic' ? $this->audience($campaign->audience_filters) : Customer::query()->whereIn('id', $campaign->recipient_ids);
            $seen = [];
            $query->with('subscriptions')->chunkById(100, function ($customers) use ($campaign, &$seen): void {
                foreach ($customers as $customer) {
                    try {
                        $content = app(MessageTemplateService::class)->render($campaign->content, app(MessageTemplateService::class)->customerData($customer));
                        $renderError = null;
                    } catch (ValidationException) {
                        $content = $campaign->content;
                        $renderError = 'Template values are incompatible with the Lookup template.';
                    }
                    foreach ($campaign->channels as $channel) {
                        $destination = $channel === 'email' ? $customer->email : ($customer->mobile ?: $customer->phone);
                        $normalized = $channel === 'email' ? strtolower(trim($destination ?? '')) : preg_replace('/\s+/', '', $destination ?? '');
                        $duplicate = $normalized !== '' && isset($seen[$channel.':'.$normalized]);
                        $seen[$channel.':'.$normalized] = true;
                        $delivery = app(CommunicationDeliveryService::class)->create($campaign->tenant_id, 'campaign:'.$campaign->id.':'.$customer->id.':'.$channel, [
                            'campaign_id' => $campaign->id, 'recipient_type' => 'customer', 'recipient_id' => $customer->id,
                            'channel' => $channel, 'destination' => $destination, 'content' => $content,
                            'provider' => $channel === 'sms' ? app(SmsGatewayService::class)->settings($campaign->tenant_id)['provider'] : 'smtp',
                            'expires_at' => now()->addDay(),
                        ]);
                        $reason = $renderError ?? ($duplicate ? 'Duplicate destination in campaign.' : app(CommunicationDeliveryService::class)->skipReason($delivery));
                        if ($reason) {
                            $delivery->update(['status' => 'skipped', 'last_error' => $reason]);
                        }
                    }
                }
            });
            $campaign->update(['materialized_at' => now()]);
        });
    }

    public function finish(Campaign $campaign): void
    {
        if (! in_array($campaign->status, ['sending', 'completed', 'completed_with_failures'], true) || ! $campaign->materialized_at) {
            return;
        }
        $statuses = $campaign->deliveries()->pluck('status');
        if ($statuses->intersect(['pending', 'retrying', 'sending'])->isNotEmpty()) {
            return;
        }
        $status = $statuses->intersect(['failed', 'unknown'])->isNotEmpty() ? 'completed_with_failures' : 'completed';
        if ($campaign->status !== $status) {
            $campaign->update(['status' => $status, 'completed_at' => $campaign->completed_at ?? now()]);
        }
    }
}
