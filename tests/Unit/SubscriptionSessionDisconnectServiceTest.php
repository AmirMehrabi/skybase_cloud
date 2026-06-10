<?php

namespace Tests\Unit;

use App\Models\Router;
use App\Models\Subscription;
use App\Services\RouterOs\RouterOsClient;
use App\Services\SubscriptionSessionDisconnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class SubscriptionSessionDisconnectServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_radius_session_uses_the_most_recent_open_radacct_row(): void
    {
        DB::table('radacct')->insert([
            'acctsessionid' => '8120002e',
            'acctuniqueid' => 'unique-8120002e',
            'username' => 'john.doe',
            'nasipaddress' => '192.168.88.1',
            'acctstarttime' => now()->subHour(),
            'acctupdatetime' => now()->subMinutes(50),
            'acctstoptime' => null,
            'acctsessiontime' => 900,
            'framedipaddress' => '10.10.10.54',
        ]);

        DB::table('radacct')->insert([
            'acctsessionid' => '8120002f',
            'acctuniqueid' => 'unique-8120002f',
            'username' => 'john.doe',
            'nasipaddress' => '192.168.88.1',
            'acctstarttime' => now()->subMinutes(30),
            'acctupdatetime' => now()->subMinutes(10),
            'acctstoptime' => null,
            'acctsessiontime' => 1800,
            'framedipaddress' => '10.10.10.55',
        ]);

        $service = app(SubscriptionSessionDisconnectService::class);
        $subscription = new Subscription([
            'pppoe_username' => 'john.doe',
        ]);

        $reflection = new ReflectionMethod($service, 'activeRadiusSession');
        $reflection->setAccessible(true);

        $session = $reflection->invoke($service, $subscription);

        $this->assertNotNull($session);
        $this->assertSame('8120002f', $session->acctsessionid);
        $this->assertSame('192.168.88.1', $session->nasipaddress);
        $this->assertSame('10.10.10.55', $session->framedipaddress);
    }

    public function test_disconnect_via_routeros_api_falls_back_to_firewall_connections_when_ppp_active_is_empty(): void
    {
        DB::table('radacct')->insert([
            'acctsessionid' => '8120002f',
            'acctuniqueid' => 'unique-8120002f',
            'username' => 'john.doe',
            'nasipaddress' => '192.168.88.1',
            'acctstarttime' => now()->subMinutes(30),
            'acctupdatetime' => now()->subMinutes(10),
            'acctstoptime' => null,
            'acctsessiontime' => 1800,
            'framedipaddress' => '10.10.10.55',
        ]);

        $service = app(SubscriptionSessionDisconnectService::class);
        $subscription = new Subscription([
            'connection_type' => 'pppoe',
            'pppoe_username' => 'john.doe',
            'ip_address' => '10.10.10.55',
        ]);
        $subscription->setRelation('router', new Router([
            'ip_address' => '192.168.88.1',
            'name' => 'Landing Station',
            'vendor' => 'Mikrotik',
            'enable_provisioning' => true,
            'api_username' => 'admin',
            'api_password' => 'secret',
        ]));

        $client = new class extends RouterOsClient
        {
            public array $sent = [];

            public function execute($router, callable $callback, ?int $timeoutSeconds = null): mixed
            {
                return $callback(new class($this)
                {
                    public function __construct(private object $client) {}
                }, $this);
            }

            public function writeSentence($connection, array $words): void
            {
                $this->sent[] = $words;
            }

            public function readResponse($connection): array
            {
                $last = $this->sent[array_key_last($this->sent)] ?? [];

                if (($last[0] ?? null) === '/ppp/active/print') {
                    return [];
                }

                if (($last[0] ?? null) === '/ip/firewall/connection/print') {
                    return [
                        [
                            '.id' => '*1',
                            'src-address' => '10.10.10.55',
                        ],
                    ];
                }

                return [];
            }
        };

        $this->app->instance(RouterOsClient::class, $client);

        $result = $service->disconnect($subscription);

        $this->assertTrue($result->wasSuccessful());
        $this->assertSame('routeros-api', $result->method);
        $this->assertSame(1, $result->sessionsRemoved);
    }
}
