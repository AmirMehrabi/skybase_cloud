<?php

namespace App\Jobs;

use App\Models\DeviceAction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AcsClient;
use App\Services\AcsService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunAcsAction implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(public string $tenantId, public int $actionId) {}

    public function handle(AcsClient $client): void
    {
        app(TenantContext::class)->run($this->tenantId, function () use ($client): void {
            $action = DeviceAction::query()->with('device.connection')->find($this->actionId);
            if (! $action || $action->status !== 'queued') {
                return;
            }
            if (! Tenant::query()->find($this->tenantId)?->acs_enabled) {
                $action->update(['status' => 'cancelled', 'payload' => null, 'finished_at' => now(), 'message' => 'ACS access was disabled before submission.']);

                return;
            }
            $automatic = $action->operation === 'auto_provision';
            if ($automatic) {
                $action->device->load(['subscription', 'profile']);
                if (! $action->device->connection->automation_enabled || ! $action->device->subscription || $action->device->subscription->status !== 'active') {
                    $action->update(['status' => 'cancelled', 'payload' => null, 'finished_at' => now(), 'message' => 'Automatic provisioning is no longer enabled for this active service.']);

                    return;
                }
            } else {
                $actor = User::query()->where('tenant_id', $this->tenantId)->find($action->user_id);
                if (! $actor || $actor->status !== 'active' || ! $actor->hasPermission('acs.actions')) {
                    $action->update(['status' => 'cancelled', 'payload' => null, 'finished_at' => now(), 'message' => 'Operator no longer has access.']);

                    return;
                }
                $previous = auth()->user();
                auth()->setUser($actor);
                try {
                    $visible = app(AcsService::class)->visibleDevices()->whereKey($action->customer_device_id)->exists();
                } finally {
                    if ($previous) {
                        auth()->setUser($previous);
                    } else {
                        auth()->forgetUser();
                    }
                }
                if (! $visible) {
                    $action->update(['status' => 'cancelled', 'payload' => null, 'finished_at' => now(), 'message' => 'Device access changed.']);

                    return;
                }
            }
            $payload = $action->payload;
            if (isset($payload['_skybase'])) {
                $action->device->load('subscription');
                $snapshot = $payload['_skybase'];
                if ($snapshot['subscription_id'] !== $action->device->subscription_id || $snapshot['profile_id'] !== $action->device->provisioning_profile_id || $snapshot['subscription_updated_at'] !== $action->device->subscription?->updated_at?->toISOString() || $action->device->subscription?->status !== 'active') {
                    $action->update(['status' => 'cancelled', 'payload' => null, 'finished_at' => now(), 'message' => 'Service configuration changed before submission. Apply the current profile again.']);

                    return;
                }
                unset($payload['_skybase']);
            }
            if (DeviceAction::query()->whereKey($action->id)->where('status', 'queued')->update(['status' => 'submitting', 'submitted_at' => now()]) !== 1) {
                return;
            }
            try {
                $result = $client->submit($action->device->connection, $action->device->external_id, $payload);
                $action->update(['status' => $result['completed'] ? 'completed' : 'pending', 'external_task_id' => $result['id'], 'finished_at' => $result['completed'] ? now() : null, 'payload' => null, 'message' => $result['completed'] ? 'Device confirmed the operation.' : 'Waiting for the next device contact.']);
            } catch (\Throwable) {
                $action->update(['status' => 'uncertain', 'payload' => null, 'message' => 'Submission could not be confirmed. Check ACS before submitting again.']);
            }
        });
    }
}
