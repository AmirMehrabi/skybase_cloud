<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\VpnUser;
use App\Services\Monitoring\OpenVpnStatusParser;
use App\Services\Monitoring\VpnStatusCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class VpnStatusMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private string $snapshotFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->snapshotFile = tempnam(sys_get_temp_dir(), 'vpn-test-');
        config(['vpn.status_file' => $this->snapshotFile, 'vpn.reservations_file' => $this->snapshotFile.'.json']);
        $this->travelTo(now()->startOfSecond());
    }

    protected function tearDown(): void
    {
        @unlink($this->snapshotFile);
        @unlink($this->snapshotFile.'.json');
        parent::tearDown();
    }

    private function snapshot(array $names = ['alpha'], ?int $timestamp = null, int $received = 1200): string
    {
        $time = $timestamp ?? now()->timestamp;
        $rows = ["TIME,date,{$time}", 'HEADER,CLIENT_LIST,Common Name,Real Address,Virtual Address,Bytes Received,Bytes Sent,Connected Since (time_t),Username,Future Field'];
        foreach ($names as $name) {
            $rows[] = "CLIENT_LIST,{$name},udp4:192.0.2.1:1234,10.8.0.100,{$received},800,".($time - 60).",{$name},future";
        }
        $rows[] = 'END';

        return implode("\n", $rows)."\n";
    }

    private function collect(string $snapshot): void
    {
        file_put_contents($this->snapshotFile, $snapshot);
        app(VpnStatusCollector::class)->collect();
    }

    public function test_collects_client_counters_ips_reservation_and_session_time(): void
    {
        $tenant = Tenant::factory()->create();
        $user = VpnUser::factory()->create(['tenant_id' => $tenant->id, 'username' => 'alpha', 'active' => false]);
        file_put_contents($this->snapshotFile.'.json', json_encode(['alpha' => '10.8.0.100']));
        $this->collect($this->snapshot());
        $user->refresh();
        $this->assertTrue($user->online);
        $this->assertFalse($user->active);
        $this->assertSame('Connected', $user->connectionState());
        $this->assertSame(1200, $user->bytes_received);
        $this->assertSame(800, $user->bytes_sent);
        $this->assertSame('10.8.0.100', $user->vpn_ip);
        $this->assertSame('10.8.0.100', $user->reserved_vpn_ip);
        $this->assertTrue($user->last_seen_at->equalTo(now()));
        $this->assertTrue($user->connected_at->equalTo(now()->subMinute()));
    }

    public function test_empty_valid_snapshot_disconnects_and_clears_current_session_counters(): void
    {
        $user = VpnUser::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'username' => 'alpha']);
        $this->collect($this->snapshot());
        $seen = $user->fresh()->last_seen_at;
        $this->travel(1)->minutes();
        $this->collect($this->snapshot([]));
        $user->refresh();
        $this->assertSame('Disconnected', $user->connectionState());
        $this->assertNull($user->vpn_ip);
        $this->assertSame(0, $user->bytes_received);
        $this->assertTrue($user->last_seen_at->equalTo($seen));
        $this->assertTrue($user->disconnected_at->equalTo(now()));
        $this->travel(1)->minutes();
        $this->collect($this->snapshot([]));
        $this->assertTrue($user->fresh()->disconnected_at->equalTo(now()->subMinute()));
    }

    public function test_reconnect_resets_counters_and_disconnection_time(): void
    {
        $user = VpnUser::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'username' => 'alpha']);
        $this->collect($this->snapshot());
        $this->travel(1)->minutes();
        $this->collect($this->snapshot([]));
        $this->travel(1)->minutes();
        $this->collect($this->snapshot(received: 10));
        $this->assertSame(10, $user->fresh()->bytes_received);
        $this->assertNull($user->fresh()->disconnected_at);
    }

    public function test_stale_snapshot_is_rejected_without_disconnecting_users(): void
    {
        $user = VpnUser::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'username' => 'alpha']);
        $this->collect($this->snapshot());
        try {
            $this->collect($this->snapshot([], now()->subMinutes(10)->timestamp));
            $this->fail('Stale snapshot accepted');
        } catch (RuntimeException) {
            $this->assertTrue($user->fresh()->online);
        }
        $this->travel(4)->minutes();
        $this->assertSame('Unknown', $user->fresh()->connectionState());
    }

    public function test_partial_snapshot_and_invalid_counters_are_rejected(): void
    {
        foreach ([str_replace('END', '', $this->snapshot()), str_replace(',1200,', ',-2,', $this->snapshot()), $this->snapshot(['alpha', 'alpha']), str_replace('Username', 'Missing Identity', $this->snapshot([])), $this->snapshot(timestamp: now()->addMinutes(3)->timestamp)] as $contents) {
            try {
                app(OpenVpnStatusParser::class)->parse($contents);
                $this->fail('Invalid snapshot accepted');
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_duplicate_login_names_across_tenants_are_not_assigned_connection_data(): void
    {
        $one = VpnUser::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'username' => 'alpha']);
        $two = VpnUser::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'username' => 'alpha']);
        $this->collect($this->snapshot());
        foreach ([$one, $two] as $user) {
            $this->assertNull($user->fresh()->observed_at);
            $this->assertSame('Unknown', $user->fresh()->connectionState());
        }
    }

    public function test_unmatched_login_does_not_authenticate_similar_username(): void
    {
        $user = VpnUser::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'username' => 'Alpha']);
        $this->collect($this->snapshot());
        $this->assertFalse($user->fresh()->online);
    }

    public function test_older_snapshot_does_not_overwrite_a_newer_observation(): void
    {
        $user = VpnUser::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'username' => 'alpha']);
        $this->collect($this->snapshot());
        $this->collect($this->snapshot([], now()->subMinute()->timestamp));
        $this->assertTrue($user->fresh()->online);
    }

    public function test_missing_file_returns_failure_without_changing_connections(): void
    {
        $user = VpnUser::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'username' => 'alpha', 'online' => true]);
        unlink($this->snapshotFile);
        $this->artisan('vpn:sync-status')->assertFailed();
        $this->assertTrue($user->fresh()->online);
    }

    public function test_api_scopes_data_stats_and_unknown_filter_to_current_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $viewer = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);
        $known = VpnUser::factory()->create(['tenant_id' => $tenant->id, 'username' => 'alpha']);
        VpnUser::factory()->create(['tenant_id' => $tenant->id, 'username' => 'unknown']);
        VpnUser::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'username' => 'hidden']);
        $this->collect($this->snapshot());
        $this->actingAs($viewer)->getJson(route('vpn-users.data'))->assertOk()->assertJsonFragment(['username' => 'alpha', 'bytes_received' => 1200, 'connection_state' => 'Connected'])->assertJsonMissing(['username' => 'hidden']);
        $this->travel(4)->minutes();
        $this->getJson(route('vpn-users.data', ['online' => 'unknown']))->assertOk()->assertJsonPath('pagination.total', 2)->assertJsonFragment(['username' => 'alpha', 'online' => null, 'bytes_received' => null]);
        $this->getJson(route('vpn-users.stats'))->assertOk()->assertJsonPath('unknown', 2)->assertJsonPath('online', 0)->assertJsonPath('offline', 0);
        $this->assertTrue($known->fresh()->online);
    }
}
