<?php

namespace App\Services;

use App\Models\AcsConnection;
use App\Models\CustomerDevice;
use App\Models\DeviceAction;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

class AcsService
{
    public function __construct(private AcsClient $client) {}

    public function visibleDevices(): Builder
    {
        return CustomerDevice::query()->where(function (Builder $query): void {
            $query->where(function (Builder $query): void {
                $query->whereNotNull('customer_id')->whereHas('customer')->where(function (Builder $query): void {
                    $query->whereNull('subscription_id')->orWhereHas('subscription');
                });
            });
            if (auth()->user()?->isOwner()) {
                $query->orWhereNull('customer_id');
            }
        });
    }

    public function sync(AcsConnection $connection): void
    {
        if (! Tenant::query()->find($connection->tenant_id)?->acs_enabled) {
            return;
        }
        $projection = '_id,_deviceId,_lastInform,Device.DeviceInfo,InternetGatewayDevice.DeviceInfo,Device.WiFi,InternetGatewayDevice.LANDevice';
        $skip = 0;
        do {
            $records = $this->client->records($connection, 'devices', [], ['projection' => $projection, 'limit' => 100, 'skip' => $skip]);
            foreach ($records as $record) {
                $info = data_get($record, 'Device.DeviceInfo', data_get($record, 'InternetGatewayDevice.DeviceInfo', []));
                $capabilities = [];
                $this->writableParameters($record, '', $capabilities);
                CustomerDevice::query()->updateOrCreate(['acs_connection_id' => $connection->id, 'external_id' => $record['_id']], [
                    'tenant_id' => $connection->tenant_id, 'serial_number' => data_get($record, '_deviceId._SerialNumber'), 'manufacturer' => data_get($record, '_deviceId._Manufacturer'),
                    'model' => data_get($info, 'ModelName._value', data_get($record, '_deviceId._ProductClass')), 'firmware' => data_get($info, 'SoftwareVersion._value'),
                    'last_contact_at' => $record['_lastInform'] ?? null, 'capabilities' => $capabilities,
                ]);
            }
            $skip += count($records);
        } while (count($records) === 100);
        foreach (DeviceAction::query()->where('status', 'pending')->whereHas('device', fn (Builder $q) => $q->where('acs_connection_id', $connection->id))->with('device')->get() as $action) {
            $faults = $this->client->records($connection, 'faults', ['device' => $action->device->external_id, 'channel' => 'task_'.$action->external_task_id]);
            if ($faults !== []) {
                $action->update(['status' => 'failed', 'message' => 'The device rejected this operation. Review the device configuration before trying again.', 'finished_at' => now(), 'payload' => null]);

                continue;
            }
            $tasks = $this->client->records($connection, 'tasks', ['_id' => $action->external_task_id]);
            if ($tasks === [] && $action->device->last_contact_at?->gt($action->submitted_at)) {
                $action->update(['status' => 'completed', 'message' => 'No pending ACS task or matching fault after device contact.', 'finished_at' => now(), 'payload' => null]);
            }
        }
        $connection->update(['last_synced_at' => now(), 'health' => 'connected']);
    }

    private function writableParameters(array $node, string $prefix, array &$result): void
    {
        foreach ($node as $key => $value) {
            if (str_starts_with((string) $key, '_') || ! is_array($value)) {
                continue;
            }
            $path = $prefix === '' ? $key : $prefix.'.'.$key;
            if (($value['_writable'] ?? false) && isset($value['_type'])) {
                $result[$path] = $value['_type'];
            }
            $this->writableParameters($value, $path, $result);
        }
    }
}
