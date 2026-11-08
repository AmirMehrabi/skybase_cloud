<?php

namespace App\Services\Monitoring;

use App\Models\Tenant;
use App\Models\VpnUser;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VpnStatusCollector
{
    public function __construct(private OpenVpnStatusParser $parser) {}

    public function collect(): array
    {
        $file = config('vpn.status_file');
        if (! is_readable($file) || ($contents = file_get_contents($file)) === false) {
            throw new RuntimeException('OpenVPN monitoring snapshot is unavailable.');
        }
        $snapshot = $this->parser->parse($contents);
        $reservations = [];
        if (is_readable(config('vpn.reservations_file'))) {
            $decoded = json_decode(file_get_contents(config('vpn.reservations_file')), true);
            $reservations = is_array($decoded) ? $decoded : [];
        }
        $users = collect();
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            $users = $users->concat(VpnUser::withoutGlobalScopes()->where('tenant_id', $tenantId)->get(['id', 'tenant_id', 'username', 'observed_at', 'last_seen_at', 'online']));
        }
        $duplicates = $users->groupBy(fn (VpnUser $user): string => strtolower($user->username))
            ->filter(fn ($group): bool => $group->count() > 1)->keys();
        $updated = 0;
        DB::transaction(function () use ($users, $snapshot, $reservations, $duplicates, &$updated): void {
            foreach ($users as $user) {
                if ($duplicates->contains(strtolower($user->username))) {
                    VpnUser::withoutGlobalScopes()->where('tenant_id', $user->tenant_id)->whereKey($user->id)->update(['observed_at' => null]);

                    continue;
                }
                if ($user->observed_at?->gt($snapshot['observed_at'])) {
                    continue;
                }
                $client = $snapshot['clients'][$user->username] ?? null;
                $updates = ['observed_at' => $snapshot['observed_at'], 'online' => $client !== null];
                $reservedIp = $reservations[$user->username] ?? null;
                $updates['reserved_vpn_ip'] = filter_var($reservedIp, FILTER_VALIDATE_IP) ? $reservedIp : null;
                if ($client !== null) {
                    $updates = array_merge($updates, $client, [
                        'last_seen_at' => $snapshot['observed_at'],
                        'last_login_at' => $client['connected_at'],
                        'disconnected_at' => null,
                    ]);
                } else {
                    $updates = array_merge($updates, ['vpn_ip' => null, 'real_ip' => null, 'bytes_received' => 0, 'bytes_sent' => 0]);
                    if ($user->observed_at === null) {
                        $updates['connected_at'] = null;
                        $updates['disconnected_at'] = null;
                    }
                    if ($user->online && $user->observed_at !== null && $user->last_seen_at !== null) {
                        $updates['disconnected_at'] = $snapshot['observed_at'];
                    }
                }
                VpnUser::withoutGlobalScopes()->where('tenant_id', $user->tenant_id)->whereKey($user->id)->update($updates);
                $updated++;
            }
        });

        return ['updated' => $updated, 'ambiguous' => $duplicates->count()];
    }
}
