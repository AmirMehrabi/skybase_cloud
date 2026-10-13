<?php

namespace Tests\Unit;

use App\Services\OutboundConnectionPolicy;
use App\Services\SafeRouterUsername;
use App\Services\SpreadsheetSecurity;
use App\Services\TotpService;
use App\Services\UploadScanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class SecurityServicesTest extends TestCase
{
    public function test_totp_matches_rfc_6238_sha1_vectors(): void
    {
        $service = new TotpService;
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        foreach ([59 => '94287082', 1111111109 => '07081804', 1111111111 => '14050471', 1234567890 => '89005924', 2000000000 => '69279037', 20000000000 => '65353130'] as $time => $expected) {
            $this->assertSame($expected, $service->code($secret, intdiv($time, 30), 8));
        }
    }

    public function test_totp_rejects_replay_and_malformed_codes(): void
    {
        $service = new TotpService;
        $secret = $service->secret();
        $counter = intdiv(now()->timestamp, 30);
        $code = $service->code($secret, $counter);
        $this->assertSame($counter, $service->matchingCounter($secret, $code));
        $this->assertNull($service->matchingCounter($secret, $code, $counter));
        $this->assertNull($service->matchingCounter($secret, '12abcd'));
    }

    public function test_routeros_string_escapes_command_metacharacters(): void
    {
        $this->assertSame('"name\\"; \\$value\\\\"', SafeRouterUsername::quote('name"; $value\\'));
        $this->expectException(\InvalidArgumentException::class);
        SafeRouterUsername::quote("name\ncommand");
    }

    public function test_egress_requires_approved_networks_and_ports(): void
    {
        config(['security.outbound_networks' => ['10.20.0.0/16'], 'security.outbound_ports' => [8729]]);
        $policy = new OutboundConnectionPolicy;
        $this->assertSame('10.20.1.2', $policy->resolve('10.20.1.2', 8729));
        foreach ([['10.21.1.2', 8729], ['10.20.1.2', 6379], ['127.0.0.1', 8729], ['169.254.169.254', 8729], ['::1', 8729]] as [$host,$port]) {
            try {
                $policy->resolve($host, $port);
                $this->fail('Unapproved egress was permitted.');
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_dns_answers_cannot_smuggle_an_unapproved_address(): void
    {
        config(['security.outbound_hosts' => ['router.example'], 'security.outbound_ports' => [8729]]);
        $policy = new class extends OutboundConnectionPolicy
        {
            protected function addresses(string $host): array
            {
                return ['10.20.1.2', '127.0.0.1'];
            }
        };
        $this->expectException(RuntimeException::class);
        $policy->resolve('router.example', 8729);
    }

    public function test_csv_cells_with_formulas_are_exported_as_text(): void
    {
        foreach (['=1+1', '+cmd', '-cmd', '@SUM(A1)', "\t=1+1"] as $value) {
            $this->assertSame("'".$value, SpreadsheetSecurity::csvValue($value));
        }
        $this->assertSame(42, SpreadsheetSecurity::csvValue(42));
        $this->assertSame('Customer', SpreadsheetSecurity::csvValue('Customer'));
    }

    public function test_uploads_fail_closed_when_scanner_is_unavailable(): void
    {
        config(['security.upload_scan_required' => true, 'security.upload_scanner' => '/nonexistent/scanner']);
        $this->expectException(ValidationException::class);
        app(UploadScanner::class)->scan(UploadedFile::fake()->create('attachment.txt', 1, 'text/plain'));
    }

    public function test_spreadsheet_unpacked_size_is_bounded(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'skybase-zip-');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('xl/worksheets/sheet1.xml', str_repeat('a', 2048));
        $zip->close();
        config(['security.enforce_resource_limits' => true, 'security.spreadsheet_max_unpacked_bytes' => 1024]);
        try {
            $this->expectException(RuntimeException::class);
            app(SpreadsheetSecurity::class)->inspect($path);
        } finally {
            unlink($path);
        }
    }
}
