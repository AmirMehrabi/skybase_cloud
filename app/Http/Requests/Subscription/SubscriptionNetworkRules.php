<?php

namespace App\Http\Requests\Subscription;

use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubscriptionNetworkRules
{
    public static function rules(): array
    {
        return [
            'concurrent_devices' => ['sometimes', 'required', 'integer', 'min:1', 'max:256'],
            'ip_allocation' => ['sometimes', 'required', 'in:fixed,dynamic_pool,reserved_set'],
            'reserved_ip_addresses' => ['sometimes', 'array', 'max:256'],
            'reserved_ip_addresses.*' => ['required', 'ipv4', 'distinct'],
        ];
    }

    public static function validate(FormRequest $request, Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $subscription = $request->route('subscription');
        $subscription = $subscription instanceof Subscription ? $subscription : null;
        $count = (int) $request->input('concurrent_devices', $subscription?->concurrent_devices ?? 1);
        $mode = $request->input('ip_allocation', $subscription?->ip_allocation ?? 'fixed');
        $tenantId = tenant_id() ?? $request->user()?->tenant_id;
        $addresses = $request->input('reserved_ip_addresses', $mode === 'reserved_set' ? ($subscription?->reserved_ip_addresses ?? []) : []);

        if ($count === 1 && $mode !== 'fixed') {
            $validator->errors()->add('ip_allocation', 'Use a single fixed IP for one concurrent device.');
        }

        if ($count > 1) {
            if ($request->input('service_type', $subscription?->service_type) !== 'hotspot') {
                $validator->errors()->add('concurrent_devices', 'Multiple concurrent devices are currently supported for Hotspot subscriptions.');
            }
            if ($request->input('connection_type', $subscription?->connection_type) !== 'pppoe') {
                $validator->errors()->add('concurrent_devices', 'Multiple concurrent devices require username and password authentication.');
            }
            if ($mode === 'fixed') {
                $validator->errors()->add('ip_allocation', 'Choose automatic allocation or reserved subscription IPs for multiple devices.');
            }
            if ($request->input('ip_management', $subscription?->ip_management) !== 'system') {
                $validator->errors()->add('ip_management', 'Select System Managed and an IP pool for multiple devices.');
            }
            if (filled($request->input('ip_address'))) {
                $validator->errors()->add('ip_address', 'Use the reserved IP selector instead of a single primary IP for multiple devices.');
            }
            if (collect($request->input('ip_routes', []))->filter(fn ($row): bool => is_array($row) && filled($row['ip_address'] ?? null))->isNotEmpty()
                || $subscription?->ipRoutes()->exists()) {
                $validator->errors()->add('ip_allocation', 'Remove existing IP routes before enabling multiple devices. Routes require a fixed primary IP.');
            }
            $pool = IpPool::query()->where('tenant_id', $tenantId)->find($request->input('ip_pool_id', $subscription?->ip_pool_id));
            $routerId = $request->input('router_id', $subscription?->router_id);
            if (! $pool || (! $pool->all_devices && (string) $pool->router_id !== (string) $routerId) || $pool->status === 'disabled') {
                $validator->errors()->add('ip_pool_id', 'Choose an enabled IP pool available on the selected router.');
            }
        }

        if ($mode !== 'reserved_set' && $addresses !== []) {
            $validator->errors()->add('reserved_ip_addresses', 'Reserved addresses are only used with reserved subscription IPs.');
        }
        if ($mode === 'reserved_set') {
            if (count($addresses) !== $count) {
                $validator->errors()->add('reserved_ip_addresses', "Select exactly {$count} IP addresses, or fill the remaining addresses automatically.");
            }
            foreach ($addresses as $index => $address) {
                $ip = IpAddress::query()->where('tenant_id', $tenantId)
                    ->where('ip_pool_id', $request->input('ip_pool_id', $subscription?->ip_pool_id))
                    ->where('ip_address', $address)->first();
                $owned = $subscription && $ip?->subscription_code === $subscription->subscription_code;
                if (! $ip || (! $ip->isAvailable() && ! $owned)) {
                    $validator->errors()->add("reserved_ip_addresses.{$index}", 'This IP address is unavailable in the selected pool.');
                }
            }
        }
    }
}
