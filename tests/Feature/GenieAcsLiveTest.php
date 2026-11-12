<?php

namespace Tests\Feature;

use App\Models\AcsConnection;
use App\Services\AcsClient;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GenieAcsLiveTest extends TestCase
{
    public function test_real_cwmp_enrollment_and_queued_reboot_with_simulated_cpe(): void
    {
        $endpoint = config('acs.test_endpoint');
        if (! $endpoint) {
            $this->markTestSkipped('Set ACS_TEST_ENDPOINT to explicitly enable the live ACS test.');
        }
        config(['acs.allowed_endpoints' => [$endpoint]]);
        $connection = new AcsConnection(['base_url' => $endpoint]);
        $serial = 'SKYBASETEST'.bin2hex(random_bytes(5));
        $deviceId = 'A1B2C3-SkyBaseTest-'.$serial;
        $client = app(AcsClient::class);
        $cwmp = Http::withBasicAuth(config('acs.test_username'), config('acs.test_password'))->timeout(20)->withOptions(['cookies' => new CookieJar])->withHeaders(['Content-Type' => 'text/xml; charset=utf-8', 'SOAPAction' => '']);
        $url = config('acs.test_cwmp_url');
        $inform = '<cwmp:Inform><DeviceId><Manufacturer>SkyBase Test</Manufacturer><OUI>A1B2C3</OUI><ProductClass>SkyBaseTest</ProductClass><SerialNumber>'.$serial.'</SerialNumber></DeviceId><Event soap-enc:arrayType="cwmp:EventStruct[1]"><EventStruct><EventCode>2 PERIODIC</EventCode><CommandKey></CommandKey></EventStruct></Event><MaxEnvelopes>1</MaxEnvelopes><CurrentTime>'.gmdate('Y-m-d\TH:i:s\Z').'</CurrentTime><RetryCount>0</RetryCount><ParameterList soap-enc:arrayType="cwmp:ParameterValueStruct[1]"><ParameterValueStruct><Name>Device.DeviceInfo.SoftwareVersion</Name><Value xsi:type="xsd:string">simulated-test-only</Value></ParameterValueStruct></ParameterList></cwmp:Inform>';
        $envelope = fn (string $body, string $id = '1'): string => '<?xml version="1.0"?><soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:soap-enc="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:cwmp="urn:dslforum-org:cwmp-1-0"><soap-env:Header><cwmp:ID soap-env:mustUnderstand="1">'.$id.'</cwmp:ID></soap-env:Header><soap-env:Body>'.$body.'</soap-env:Body></soap-env:Envelope>';
        try {
            $response = $cwmp->withBody($envelope($inform), 'text/xml')->post($url);
            $this->assertSame(200, $response->status());
            $this->assertStringContainsString('InformResponse', $response->body());
            $response = $cwmp->withBody('', 'text/xml')->post($url);
            $this->assertSame(204, $response->status());
            $records = $client->records($connection, 'devices', ['_id' => $deviceId]);
            $this->assertCount(1, $records);
            $task = Http::post($endpoint.'/devices/'.rawurlencode($deviceId).'/tasks', ['name' => 'reboot']);
            $this->assertSame(202, $task->status());
            $response = $cwmp->withBody($envelope($inform), 'text/xml')->post($url);
            $this->assertSame(200, $response->status());
            $response = $cwmp->withBody('', 'text/xml')->post($url);
            $this->assertStringContainsString('Reboot', $response->body());
            $xml = simplexml_load_string($response->body());
            $xml->registerXPathNamespace('cwmp', 'urn:dslforum-org:cwmp-1-0');
            $id = (string) $xml->xpath('//cwmp:ID')[0];
            $response = $cwmp->withBody($envelope('<cwmp:RebootResponse/>', $id), 'text/xml')->post($url);
            $this->assertSame(204, $response->status());
            $tasks = $client->records($connection, 'tasks', ['device' => $deviceId]);
            $this->assertSame([], $tasks);
        } finally {
            Http::delete($endpoint.'/devices/'.rawurlencode($deviceId))->throw();
        }
    }
}
