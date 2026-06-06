<?php

namespace Tests\Unit;

use App\Models\Subscription;
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
}
