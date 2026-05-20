<?php

namespace App\Console\Commands;

use App\Models\NetflowFlow;
use App\Models\Router;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

#[Signature('netflow:collect {--host= : UDP bind host} {--port= : UDP bind port}')]
#[Description('Collect NetFlow exports and persist parsed flow records')]
class NetflowCollect extends Command
{
    private string $outputBuffer = '';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $script = base_path('tools/netflow-collector/collector.py');
        $host = (string) ($this->option('host') ?: config('netflow.collector_bind_host'));
        $port = (string) ($this->option('port') ?: config('netflow.collector_port'));

        if (! file_exists($script)) {
            $this->error('NetFlow collector script was not found.');

            return self::FAILURE;
        }

        $process = new Process([
            (string) config('netflow.python'),
            $script,
            '--host',
            $host,
            '--port',
            $port,
        ], base_path(), null, null, null);

        $this->info("Listening for NetFlow packets on {$host}:{$port}");

        $process->run(function (string $type, string $buffer): void {
            if ($type === Process::ERR) {
                foreach (preg_split('/\R/', trim($buffer)) as $line) {
                    if ($line === '') {
                        continue;
                    }

                    $this->warn($line);
                }

                return;
            }

            $this->outputBuffer .= $buffer;

            while (($position = strpos($this->outputBuffer, "\n")) !== false) {
                $line = trim(substr($this->outputBuffer, 0, $position));
                $this->outputBuffer = substr($this->outputBuffer, $position + 1);

                if ($line === '') {
                    continue;
                }

                $this->persistFlow($line);
            }
        });

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }

    private function persistFlow(string $line): void
    {
        $payload = json_decode($line, true);

        if (! is_array($payload)) {
            $this->warn('Skipping invalid NetFlow payload: '.$line);

            return;
        }

        $router = Router::query()
            ->withoutGlobalScopes()
            ->where('ip_address', $payload['exporter_ip'] ?? null)
            ->where('netflow_enabled', true)
            ->first();

        if (! $router) {
            return;
        }

        $receivedAt = Carbon::parse($payload['received_at'] ?? now());

        NetflowFlow::query()->withoutGlobalScopes()->create([
            'tenant_id' => $router->tenant_id,
            'router_id' => $router->id,
            'exporter_ip' => $payload['exporter_ip'] ?? null,
            'source_ip' => $payload['source_ip'],
            'destination_ip' => $payload['destination_ip'],
            'source_port' => $this->nullableInteger($payload['source_port'] ?? null),
            'destination_port' => $this->nullableInteger($payload['destination_port'] ?? null),
            'protocol' => $this->nullableInteger($payload['protocol'] ?? null),
            'bytes' => (int) ($payload['bytes'] ?? 0),
            'packets' => (int) ($payload['packets'] ?? 0),
            'received_at' => $receivedAt,
        ]);

        $router->forceFill([
            'netflow_last_packet_at' => $receivedAt,
            'netflow_test_status' => $router->netflow_test_status === 'pending' ? 'received' : $router->netflow_test_status,
            'netflow_error' => null,
        ])->save();
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
