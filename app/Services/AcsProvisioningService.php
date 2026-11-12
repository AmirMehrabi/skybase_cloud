<?php

namespace App\Services;

use App\Jobs\RunAcsAction;
use App\Models\CustomerDevice;
use App\Models\DeviceAction;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

class AcsProvisioningService
{
    public function queue(CustomerDevice $device, ?int $actorId, bool $automatic = false): ?DeviceAction
    {
        return $device->getConnection()->transaction(function () use ($device, $actorId, $automatic): ?DeviceAction {
            $device = CustomerDevice::query()->lockForUpdate()->findOrFail($device->id);
            $device->load(['profile', 'subscription', 'connection']);
            if (! Tenant::query()->find($device->tenant_id)?->acs_enabled || ! $device->profile || ! $device->subscription || $device->subscription->status !== 'active') {
                return null;
            }
            if ($automatic && ! $device->connection->automation_enabled) {
                return null;
            }
            if ($device->profile->manufacturer !== $device->manufacturer || $device->profile->model !== $device->model) {
                return null;
            }
            if ($device->actions()->whereIn('status', ['queued', 'submitting', 'pending', 'uncertain'])->exists()) {
                return null;
            }
            $values = [];
            foreach ($device->profile->parameters as $parameter) {
                $path = $parameter['path'];
                if (! isset($device->capabilities[$path])) {
                    throw ValidationException::withMessages(['parameters' => 'A profile parameter is no longer writable. Refresh the device and review the profile.']);
                }
                $value = strtr($parameter['value'], ['{{pppoe_username}}' => $device->subscription->pppoe_username ?? '', '{{pppoe_password}}' => $device->subscription->pppoe_password ?? '']);
                $values[] = [$path, $value, $device->capabilities[$path]];
            }
            $action = $device->actions()->create(['tenant_id' => $device->tenant_id, 'user_id' => $actorId, 'operation' => $automatic ? 'auto_provision' : 'provision', 'payload' => ['name' => 'setParameterValues', 'parameterValues' => $values, '_skybase' => ['subscription_id' => $device->subscription_id, 'profile_id' => $device->provisioning_profile_id, 'subscription_updated_at' => $device->subscription->updated_at?->toISOString()]], 'message' => 'Profile '.$device->profile->name.' revision '.$device->profile->revision]);
            RunAcsAction::dispatch($device->tenant_id, $action->id)->afterCommit();

            return $action;
        });
    }

    public function subscriptionChanged(Subscription $subscription): void
    {
        if (! $subscription->wasChanged(['status', 'plan_id', 'pppoe_username', 'pppoe_password']) || ! Tenant::query()->find($subscription->tenant_id)?->acs_enabled) {
            return;
        }
        foreach (CustomerDevice::query()->where('tenant_id', $subscription->tenant_id)->where('subscription_id', $subscription->id)->get() as $device) {
            try {
                $this->queue($device, null, true);
            } catch (\Throwable) {
                report(new \RuntimeException('ACS automatic provisioning could not be queued for device '.$device->id));
            }
        }
    }
}
