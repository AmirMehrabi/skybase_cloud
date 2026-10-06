<?php

namespace Tests\Unit;

use App\Models\Router;
use App\Models\Subscription;
use App\Services\Monitoring\RrdToolService;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RrdToolServiceTest extends TestCase
{
    public function test_monitoring_archives_remain_group_writable_with_a_restrictive_umask(): void
    {
        $rrdTool = new RrdToolService;

        if (! $rrdTool->isAvailable()) {
            $this->markTestSkipped('RRDTool is not installed.');
        }

        $root = sys_get_temp_dir().'/skybase-rrd-permissions-'.uniqid();
        config(['monitoring.rrd_root' => $root]);
        $router = new Router(['tenant_id' => 'tenant-test']);
        $router->id = 42;
        $subscription = new Subscription(['tenant_id' => 'tenant-test']);
        $subscription->id = 43;
        $previousUmask = umask(0077);

        try {
            $rrdTool->updateRouterHealth($router, [
                'latency_ms' => 1.5,
                'packet_loss_percent' => 0,
                'online' => true,
                'cpu_usage' => 23,
                'memory_usage' => 41,
                'active_sessions_count' => 2,
            ]);
            $rrdTool->updateSubscriptionBandwidth($subscription, ['rx_bps' => 1000, 'tx_bps' => 500]);

            clearstatcache();
            $this->assertSame(02775, fileperms($root.'/tenant-test') & 07777);
            $this->assertSame(0664, fileperms($root.'/tenant-test/router-42-health.rrd') & 0777);
            $this->assertSame(0664, fileperms($root.'/tenant-test/subscription-43-bandwidth.rrd') & 0777);
        } finally {
            umask($previousUmask);
            File::deleteDirectory($root);
        }
    }

    public function test_legacy_subscription_archive_is_backed_up_and_recreated_with_five_minute_step(): void
    {
        $rrdTool = new RrdToolService;

        if (! $rrdTool->isAvailable()) {
            $this->markTestSkipped('RRDTool is not installed.');
        }

        $root = sys_get_temp_dir().'/skybase-rrd-test-'.uniqid();
        config([
            'monitoring.rrd_root' => $root,
            'monitoring.subscription_step_seconds' => 300,
        ]);

        $subscription = new Subscription([
            'tenant_id' => 'tenant-test',
        ]);
        $subscription->id = 42;
        $path = "{$root}/tenant-test/subscription-42-bandwidth.rrd";

        File::ensureDirectoryExists(dirname($path));
        $this->runRrd([
            'rrdtool',
            'create',
            $path,
            '--step',
            '60',
            'DS:rx_bps:GAUGE:180:0:U',
            'DS:tx_bps:GAUGE:180:0:U',
            'RRA:AVERAGE:0.5:1:10',
        ]);

        try {
            $rrdTool->updateSubscriptionBandwidth($subscription, [
                'rx_bps' => 12000000,
                'tx_bps' => 3000000,
            ]);

            $info = $this->runRrd(['rrdtool', 'info', $path]);

            $this->assertMatchesRegularExpression('/^step\\s*=\\s*300$/m', $info);
            $this->assertCount(1, glob($path.'.legacy-step-60-*') ?: []);
        } finally {
            File::deleteDirectory($root);
        }
    }

    /**
     * @param  list<string>  $command
     */
    private function runRrd(array $command): string
    {
        $process = new Process($command);
        $process->mustRun();

        return $process->getOutput();
    }
}
