<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailConnectionTester;
use App\Services\TenantMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class EmailConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_smtp_test_starts_and_stops_an_authenticated_transport(): void
    {
        $settings = ['host' => 'smtp.example.com', 'username' => 'user', 'password' => 'secret', 'encryption' => 'tls', 'port' => 587];
        $transport = Mockery::mock(EsmtpTransport::class);
        $transport->shouldReceive('start')->once();
        $transport->shouldReceive('stop')->once();
        $this->mock(TenantMailService::class, fn ($mock) => $mock->shouldReceive('transport')->with($settings)->once()->andReturn($transport));
        app(EmailConnectionTester::class)->test($settings, 'outgoing');
    }

    public function test_imap_parser_checks_tagged_result_and_sends_the_command(): void
    {
        [$client,$server] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($server, "* CAPABILITY IMAP4rev1\r\na2 OK authenticated\r\n");
        $tester = new class extends EmailConnectionTester
        {
            public function execute(mixed $socket): void
            {
                $this->command($socket, 'a2 LOGIN "user" "secret"', 'a2');
            }
        };
        $tester->execute($client);
        $this->assertSame("a2 LOGIN \"user\" \"secret\"\r\n", fgets($server));
        fclose($client);
        fclose($server);
    }

    public function test_imap_authentication_rejection_is_not_reported_as_success(): void
    {
        [$client,$server] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        fwrite($server, "a2 NO invalid password\r\n");
        $tester = new class extends EmailConnectionTester
        {
            public function execute(mixed $socket): void
            {
                $this->command($socket, 'a2 LOGIN "user" "secret"', 'a2');
            }
        };
        try {
            $tester->execute($client);
            $this->fail('Authentication failure should throw.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('rejected', $exception->getMessage());
        } finally {
            fclose($client);
            fclose($server);
        }
    }

    public function test_settings_test_uses_only_the_current_tenant_saved_credentials(): void
    {
        $tenant = Tenant::create(['id' => (string) Str::uuid(), 'name' => 'Mail', 'slug' => 'mail-test', 'company_name' => 'Mail', 'email' => 'mail@example.com', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($user);
        app()->instance('current_tenant', $tenant);
        Setting::set('email.incoming', ['host' => 'imap.example.com', 'port' => 993, 'encryption' => 'ssl', 'protocol' => 'imap', 'username' => 'user', 'password' => 'private-password', 'mailbox' => 'INBOX'], 'json', $tenant->id);
        $this->mock(EmailConnectionTester::class, fn ($mock) => $mock->shouldReceive('test')->once()->withArgs(fn ($settings, $direction) => $settings['password'] === 'private-password' && $direction === 'incoming'));
        $this->post(route('settings.test.email'), ['direction' => 'incoming'])->assertSessionHas('success');
        $this->assertStringNotContainsString('private-password', Setting::query()->first()->getRawOriginal('value'));
    }
}
