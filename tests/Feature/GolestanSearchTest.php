<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\GolestanService;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class GolestanSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.golestan.enabled' => true, 'services.golestan.username' => 'test-user', 'services.golestan.password' => 'test-password']);
    }

    public function test_disabled_integration_returns_404_for_guests_and_admins(): void
    {
        config(['services.golestan.enabled' => false]);
        Http::preventStrayRequests();
        $this->get(route('golestan.index'))->assertNotFound();
        $this->post(route('golestan.search'), $this->payload())->assertNotFound();
        $this->actingAs($this->user())->get(route('golestan.index'))->assertNotFound();
        $this->post(route('golestan.search'), $this->payload())->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_posted_credentials_cannot_override_environment_credentials(): void
    {
        Http::fake(['*' => Http::response($this->soap('<Root/>'))]);
        $this->actingAs($this->user())->post(route('golestan.search'), $this->payload() + ['username' => 'override', 'password' => 'override-password'])->assertOk();
        Http::assertSent(function (Request $request): bool {
            $document = new DOMDocument;
            $document->loadXML($request->body());
            $xpath = new DOMXPath($document);

            return $xpath->evaluate('string(//*[local-name()="login"])') === 'test-user'
                && $xpath->evaluate('string(//*[local-name()="pass"])') === 'test-password';
        });
    }

    public function test_filters_match_the_working_browser_form(): void
    {
        $document = new DOMDocument;
        $document->loadXML(app(GolestanService::class)->requestXml('92133002'));
        $xpath = new DOMXPath($document);
        $expectedPub = <<<'XML'
<Root>
<N id="1" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="2" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="4" fs="1" ft="7" F1="92133002" T1="92133002" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="6" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="7" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="8" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="10" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="18" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S="0"/>
<N id="20" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S="0"/>
<N id="22" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S="0"/>
<N id="24" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S="0"/>
<N id="26" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S="0"/>
<N id="27" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="29" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="30" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="32" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="34" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="36" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="37" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="38" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="40" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="42" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="48" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="101" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="102" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
<N id="103" fs="1" ft="7" F1="" T1="" F2="" T2="" B=" " Q="0" A=" " S=" "/>
</Root>
XML;
        $expected = new DOMDocument;
        $expected->preserveWhiteSpace = false;
        $expected->loadXML($expectedPub);
        $actual = new DOMDocument;
        $actual->preserveWhiteSpace = false;
        $actual->loadXML($xpath->evaluate('string(//*[local-name()="pub"])'));
        $this->assertSame($expected->C14N(), $actual->C14N());

        $expectedPri = <<<'XML'
<Root>
<N id="5" fs="0" ft="7" UQID="245" F="2" T="2"/>
<N id="10" fs="0" ft="7" UQID="10" F="" T=""/>
<N id="20" fs="0" ft="7" UQID="153" F="" T=""/>
<N id="24" fs="0" ft="7" UQID="17450" F="" T=""/>
</Root>
XML;
        $expected = new DOMDocument;
        $expected->preserveWhiteSpace = false;
        $expected->loadXML($expectedPri);
        $actual = new DOMDocument;
        $actual->preserveWhiteSpace = false;
        $actual->loadXML($xpath->evaluate('string(//*[local-name()="pri"])'));
        $this->assertSame($expected->C14N(), $actual->C14N());

        $expectedMor = <<<'XML'
<Root/>
XML;
        $expected = new DOMDocument;
        $expected->preserveWhiteSpace = false;
        $expected->loadXML($expectedMor);
        $actual = new DOMDocument;
        $actual->preserveWhiteSpace = false;
        $actual->loadXML($xpath->evaluate('string(//*[local-name()="mor"])'));
        $this->assertSame($expected->C14N(), $actual->C14N());

    }

    public function test_guest_must_log_in(): void
    {
        Http::preventStrayRequests();
        $this->get(route('golestan.index'))->assertRedirect(route('auth.login'));
        $this->post(route('golestan.search'), $this->payload())->assertRedirect(route('auth.login'));
        Http::assertNothingSent();
    }

    public function test_non_admin_cannot_open_or_search(): void
    {
        Http::preventStrayRequests();
        $user = $this->user('support');
        $this->actingAs($user)->get(route('golestan.index'))->assertForbidden();
        $this->post(route('golestan.search'), $this->payload())->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_admin_can_open_page_without_calling_golestan(): void
    {
        Http::preventStrayRequests();
        $this->actingAs($this->user())->get(route('golestan.index'))
            ->assertOk()->assertSee('Golestan student search')->assertDontSee('name="password"', false)->assertDontSee('name="username"', false)->assertHeader('Cache-Control', 'no-store, private');
        Http::assertNothingSent();
    }

    public function test_owner_can_search_and_view_student_fields_and_escaped_xml(): void
    {
        Http::fake(['*' => Http::response($this->soap('<Root><Student STDNO="92133002" firstname="نام" name="خانوادگی" FACNAME="Engineering" /></Root>'))]);
        $this->actingAs($this->user('owner'))->post(route('golestan.search'), $this->payload())
            ->assertOk()->assertSee('Engineering')->assertSee('92133002')->assertSee('Results (1)')->assertSee('&lt;Root', false)
            ->assertDontSee('test-password');

        Http::assertSent(function (Request $request): bool {
            $xml = new DOMDocument;
            $xml->loadXML($request->body());
            $xpath = new DOMXPath($xml);
            $public = new DOMDocument;
            $public->loadXML($xpath->evaluate('string(//*[local-name()="pub"])'));
            $filter = (new DOMXPath($public))->query('/Root/N[@id="4"]')->item(0);

            return $request->url() === 'https://eduold.uk.ac.ir/GolestanService/gservice.asmx'
                && $request->hasHeader('SOAPAction', '"urn:nowpardaz/golInfo"')
                && $filter->getAttribute('F1') === '92133002'
                && $filter->getAttribute('T1') === '92133002'
                && $xpath->evaluate('string(//*[local-name()="iFID"])') === '1132'
                && $xpath->evaluate('string(//*[local-name()="login"])') === 'test-user'
                && $xpath->evaluate('string(//*[local-name()="pass"])') === 'test-password'
                && $xpath->evaluate('string(//*[local-name()="sec"])') === 'AA0ECD9901';
        });
        Http::assertSentCount(1);
    }

    public function test_generated_request_xml_matches_the_sent_request_with_password_masked(): void
    {
        Http::fake(['*' => Http::response($this->soap('<Root/>'))]);
        $response = $this->actingAs($this->user())->post(route('golestan.search'), $this->payload());
        $response->assertOk()->assertSee('Generated request XML')->assertDontSee('test-password');
        $preview = $response->viewData('requestXml');
        $response->assertSee($preview);
        Http::assertSent(function (Request $request) use ($preview): bool {
            $document = new DOMDocument;
            $document->loadXML($request->body());
            (new DOMXPath($document))->query('//*[local-name()="pass"]')->item(0)->nodeValue = '[REDACTED]';
            $expected = new DOMDocument;
            $expected->loadXML($preview);

            return $document->C14N() === $expected->C14N();
        });
    }

    public function test_invalid_search_is_rejected_without_sending_credentials_or_flashing_password(): void
    {
        Http::preventStrayRequests();
        $this->actingAs($this->user())->from(route('golestan.index'))
            ->post(route('golestan.search'), array_replace($this->payload(), ['student_number' => '<Root/>']))
            ->assertSessionHasErrors('student_number')->assertSessionMissing('_old_input.password');
        Http::assertNothingSent();
    }

    public function test_missing_environment_credentials_show_an_error_without_sending_a_request(): void
    {
        config(['services.golestan.password' => null]);
        Http::preventStrayRequests();
        $this->actingAs($this->user())->post(route('golestan.search'), $this->payload())
            ->assertOk()->assertSee('Golestan web-service credentials are not configured.');
        Http::assertNothingSent();
    }

    public function test_persian_digits_are_normalized(): void
    {
        Http::fake(['*' => Http::response($this->soap('<Root/>'))]);
        $this->actingAs($this->user())->post(route('golestan.search'), array_replace($this->payload(), ['student_number' => '۹۲۱۳۳۰۰۲']))
            ->assertOk()->assertSee('No student found');
        Http::assertSent(fn (Request $request): bool => str_contains($request->body(), '92133002'));
    }

    public function test_service_access_denial_is_not_reported_as_empty_results(): void
    {
        Http::fake(['*' => Http::response($this->soap('<Root><P>دسترسي به اطلاعات درخواستي وجود ندارد</P></Root>'))]);
        $this->actingAs($this->user())->post(route('golestan.search'), $this->payload())
            ->assertOk()->assertSee('دسترسي به اطلاعات درخواستي وجود ندارد')->assertDontSee('No student found')
            ->assertSee('Generated request XML')->assertSee('[REDACTED]')->assertDontSee('test-password');
    }

    public function test_connection_failure_is_displayed_without_exposing_credentials(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $this->actingAs($this->user())->post(route('golestan.search'), $this->payload())
            ->assertOk()->assertSee('Golestan could not be reached')->assertDontSee('test-password')
            ->assertSee('Generated request XML')->assertSee('[REDACTED]');
    }

    public function test_malformed_response_is_displayed_as_an_error(): void
    {
        Http::fake(['*' => Http::response('<html>Unavailable</html>')]);
        $this->actingAs($this->user())->post(route('golestan.search'), $this->payload())
            ->assertOk()->assertSee('Golestan returned an unexpected response');
    }

    public function test_soap_fault_is_displayed_as_an_error(): void
    {
        Http::fake(['*' => Http::response('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><soap:Fault><faultstring>Private server details</faultstring></soap:Fault></soap:Body></soap:Envelope>', 500)]);
        $this->actingAs($this->user())->post(route('golestan.search'), $this->payload())
            ->assertOk()->assertSee('Golestan rejected the request')->assertDontSee('Private server details');
    }

    public function test_service_accepts_element_based_records_and_ignores_other_student_numbers(): void
    {
        Http::fake(['*' => Http::response($this->soap('<Root><Student><STDNO>92133002</STDNO><firstname>Example</firstname></Student><Student><STDNO>999</STDNO><firstname>Other student</firstname></Student></Root>'))]);
        $result = app(GolestanService::class)->search('92133002');
        $this->assertCount(1, $result['records']);
        $this->assertSame('Example', $result['records'][0]['firstname']);
    }

    public function test_service_rejects_xml_entities(): void
    {
        Http::fake(['*' => Http::response('<!DOCTYPE Root [<!ENTITY secret SYSTEM "file:///etc/passwd">]><Root>&secret;</Root>')]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsupported XML');
        app(GolestanService::class)->search('92133002');
    }

    /** @return array<string, string> */
    private function payload(): array
    {
        return ['student_number' => '92133002'];
    }

    private function soap(string $result): string
    {
        return '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><golInfoResponse xmlns="urn:nowpardaz"><golInfoResult>'.$result.'</golInfoResult></golInfoResponse></soap:Body></soap:Envelope>';
    }

    private function user(string $role = 'admin'): User
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(), 'name' => 'Test University', 'slug' => 'university-'.Str::random(8),
            'company_name' => 'Test University', 'email' => 'university@example.com', 'timezone' => 'UTC', 'status' => 'active',
        ]);

        return User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role, 'status' => 'active']);
    }
}
