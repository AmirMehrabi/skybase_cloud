<?php

namespace App\Services\Netflow;

use App\Models\NetflowFlow;
use App\Models\Router;
use Illuminate\Support\Collection;

class NetflowSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function forRouter(Router $router): array
    {
        $since = now()->subHour();

        $query = NetflowFlow::query()
            ->where('tenant_id', $router->tenant_id)
            ->where('router_id', $router->id)
            ->where('received_at', '>=', $since);

        $recentFlows = (clone $query)
            ->latest('received_at')
            ->limit(15)
            ->get();

        $totalBytes = (clone $query)->sum('bytes');
        $totalPackets = (clone $query)->sum('packets');

        return [
            'stats' => [
                'flows' => (clone $query)->count(),
                'bytes' => (int) $totalBytes,
                'packets' => (int) $totalPackets,
                'throughput_bps' => (int) round(((int) $totalBytes * 8) / 3600),
                'last_packet_at' => $router->netflow_last_packet_at?->diffForHumans(),
            ],
            'top_sources' => $this->topByColumn($router, 'source_ip'),
            'top_destinations' => $this->topByColumn($router, 'destination_ip'),
            'top_protocols' => $this->topProtocols($router),
            'latest_flows' => $recentFlows->map(fn (NetflowFlow $flow): array => $this->flowRow($flow))->values(),
        ];
    }

    /**
     * @return Collection<int, array{label: string, bytes: int, packets: int, flows: int}>
     */
    private function topByColumn(Router $router, string $column): Collection
    {
        return NetflowFlow::query()
            ->where('tenant_id', $router->tenant_id)
            ->where('router_id', $router->id)
            ->where('received_at', '>=', now()->subHour())
            ->selectRaw("{$column} as label, SUM(bytes) as bytes, SUM(packets) as packets, COUNT(*) as flows")
            ->groupBy($column)
            ->orderByDesc('bytes')
            ->limit(5)
            ->get()
            ->map(fn (NetflowFlow $flow): array => [
                'label' => (string) $flow->label,
                'bytes' => (int) $flow->bytes,
                'packets' => (int) $flow->packets,
                'flows' => (int) $flow->flows,
            ]);
    }

    /**
     * @return Collection<int, array{label: string, bytes: int, packets: int, flows: int}>
     */
    private function topProtocols(Router $router): Collection
    {
        return NetflowFlow::query()
            ->where('tenant_id', $router->tenant_id)
            ->where('router_id', $router->id)
            ->where('received_at', '>=', now()->subHour())
            ->selectRaw('protocol, SUM(bytes) as bytes, SUM(packets) as packets, COUNT(*) as flows')
            ->groupBy('protocol')
            ->orderByDesc('bytes')
            ->limit(5)
            ->get()
            ->map(fn (NetflowFlow $flow): array => [
                'label' => $this->protocolName($flow->protocol),
                'bytes' => (int) $flow->bytes,
                'packets' => (int) $flow->packets,
                'flows' => (int) $flow->flows,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function flowRow(NetflowFlow $flow): array
    {
        return [
            'id' => $flow->id,
            'source' => $flow->source_port ? "{$flow->source_ip}:{$flow->source_port}" : $flow->source_ip,
            'destination' => $flow->destination_port ? "{$flow->destination_ip}:{$flow->destination_port}" : $flow->destination_ip,
            'protocol' => $this->protocolName($flow->protocol),
            'bytes' => $flow->bytes,
            'packets' => $flow->packets,
            'received_at' => $flow->received_at?->diffForHumans(),
        ];
    }

    private function protocolName(?int $protocol): string
    {
        return match ($protocol) {
            1 => 'ICMP',
            6 => 'TCP',
            17 => 'UDP',
            null => 'Unknown',
            default => 'Protocol '.$protocol,
        };
    }
}
