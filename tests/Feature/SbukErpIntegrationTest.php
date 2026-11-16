<?php

namespace Tests\Feature;

use App\Jobs\ProcessSbukSyncJob;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\SbukSyncRun;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SbukErpClient;
use App\Services\SbukPersonnelImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class SbukErpIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.sbuk_erp.enabled' => true,
            'services.sbuk_erp.base_url' => 'https://erp.uk.ac.ir',
            'services.sbuk_erp.service_id' => 2112,
            'services.sbuk_erp.token' => 'test-erp-token',
        ]);
    }

    public function test_bulk_request_uses_server_token_and_expected_payload(): void
    {
        Http::fake(['*' => Http::response([['FNAME' => 'Ali', 'LNAME' => 'Rezaei', 'NATIONALID' => '0012345678']])]);
        $records = app(SbukErpClient::class)->personnel();

        $this->assertSame('0012345678', $records[0]['NATIONALID']);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://erp.uk.ac.ir/ServiceBuilder'
            && $request->hasHeader('Authorization', 'test-erp-token')
            && $request->data() === ['serviceId' => 2112]);
    }

    public function test_erp_http_and_response_errors_are_rejected(): void
    {
        Http::fakeSequence()->push(['message' => 'denied'], 401)->push('{broken', 200);
        try {
            app(SbukErpClient::class)->personnel();
            $this->fail('Expected the ERP HTTP error to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 401', $exception->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid JSON');
        app(SbukErpClient::class)->personnel();
    }

    public function test_empty_dataset_is_valid_but_unexpected_structure_is_rejected(): void
    {
        Http::fakeSequence()->push([])->push(['records' => []]);
        $this->assertSame([], app(SbukErpClient::class)->personnel());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected response structure');
        app(SbukErpClient::class)->personnel();
    }

    public function test_connection_failure_is_reported_without_credentials(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('could not be reached');
        app(SbukErpClient::class)->personnel();
    }

    public function test_json_encoded_array_response_is_unwrapped_and_validated(): void
    {
        Http::fake(['*' => Http::response(json_encode(json_encode([['FNAME' => 'Ali']], JSON_THROW_ON_ERROR), JSON_THROW_ON_ERROR))]);
        $this->assertSame([['FNAME' => 'Ali']], app(SbukErpClient::class)->personnel());
    }

    public function test_personnel_normalization_preserves_leading_zero_and_normalizes_persian_digits(): void
    {
        $records = app(SbukPersonnelImportService::class)->normalize([[
            'FNAME' => '  علی ',
            'LNAME' => ' رضایی ',
            'MOBILE' => '۰۹۱۲۰۰۰۰۰۰۰',
            'NATIONALID' => '۰۰۱۲۳۴۵۶۷۸',
            'index_' => 12,
        ], ['FNAME' => 'Missing id', 'LNAME' => 'User']]);

        $this->assertSame('علی', $records[0]['first_name']);
        $this->assertSame('0012345678', $records[0]['national_id']);
        $this->assertSame('09120000000', $records[0]['mobile']);
        $this->assertSame('12', $records[0]['external_index']);
        $this->assertSame('National ID is missing or invalid.', $records[1]['error']);
    }

    public function test_admin_can_preview_without_mutating_customer_or_subscription_data_and_start_queues_run(): void
    {
        Queue::fake();
        Http::fake(['*' => Http::response([[
            'FNAME' => 'Ali', 'LNAME' => 'Rezaei', 'MOBILE' => '09120000000', 'NATIONALID' => '0012345678', 'index_' => 4,
        ]])]);
        $user = $this->user();
        $router = Router::factory()->create(['tenant_id' => $user->tenant_id, 'status' => 'online']);
        $plan = Plan::factory()->create(['tenant_id' => $user->tenant_id]);
        $pool = IpPool::factory()->active()->create(['tenant_id' => $user->tenant_id, 'router_id' => $router->id]);

        $previewResponse = $this->actingAs($user)->postJson(route('integrations.sbuk-erp.preview'), [
            'plan_id' => $plan->id, 'router_id' => $router->id, 'ip_pool_id' => $pool->id,
        ])->assertOk()->assertJsonPath('summary.total_records', 1)->assertJsonPath('summary.new_users', 1);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        $previewToken = $previewResponse->json('preview_token');
        $this->actingAs($user)->postJson(route('integrations.sbuk-erp.start'), ['preview_token' => $previewToken])
            ->assertOk()->assertJsonStructure(['run_id', 'status_url']);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertSame(1, SbukSyncRun::query()->where('tenant_id', $user->tenant_id)->count());
        Queue::assertPushed(ProcessSbukSyncJob::class);
    }

    public function test_guests_and_non_admins_cannot_access_synchronization(): void
    {
        Http::preventStrayRequests();
        $this->get(route('integrations.sbuk-erp.index'))->assertRedirect(route('auth.login'));
        $this->actingAs($this->user('support'))->get(route('integrations.sbuk-erp.index'))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_admin_can_open_the_integration_page_without_fetching_erp(): void
    {
        Http::preventStrayRequests();
        $this->actingAs($this->user())->get(route('integrations.sbuk-erp.index'))
            ->assertOk()->assertSee('SBUK ERP — Personnel synchronization')->assertSee('Fetch')
            ->assertDontSee('test-erp-token')->assertHeader('Cache-Control', 'no-store, private');
        Http::assertNothingSent();
    }

    public function test_reprocessing_same_personnel_creates_no_duplicate_customer_subscription_or_ip(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $router = Router::factory()->create(['tenant_id' => $user->tenant_id, 'status' => 'online']);
        $plan = Plan::factory()->create(['tenant_id' => $user->tenant_id]);
        $pool = IpPool::factory()->active()->create(['tenant_id' => $user->tenant_id, 'router_id' => $router->id]);
        $address = IpAddress::query()->create([
            'tenant_id' => $user->tenant_id, 'ip_pool_id' => $pool->id, 'ip_address' => '10.10.0.10', 'status' => 'available',
        ]);
        $person = app(SbukPersonnelImportService::class)->normalize([[
            'FNAME' => 'Ali', 'LNAME' => 'Rezaei', 'MOBILE' => '09120000000', 'NATIONALID' => '0012345678', 'index_' => 'ERP-12',
        ]])[0];

        foreach ([1, 2] as $iteration) {
            $run = SbukSyncRun::query()->create([
                'tenant_id' => $user->tenant_id,
                'triggered_by' => $user->id,
                'plan_id' => $plan->id,
                'router_id' => $router->id,
                'ip_pool_id' => $pool->id,
                'status' => 'pending',
                'total_records' => 1,
                'valid_records' => 1,
                'personnel_snapshot' => json_encode([$person], JSON_THROW_ON_ERROR),
            ]);
            app(SbukPersonnelImportService::class)->process($run);
        }

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseHas('subscriptions', ['status' => 'pending', 'billing_enabled' => false]);
        $this->assertDatabaseHas('ip_addresses', ['id' => $address->id, 'status' => 'assigned']);
        $this->assertDatabaseHas('sbuk_sync_runs', ['tenant_id' => $user->tenant_id, 'created_subscriptions' => 1]);
        $this->assertDatabaseHas('sbuk_sync_runs', ['tenant_id' => $user->tenant_id, 'skipped_subscriptions' => 1]);
        $this->assertDatabaseHas('sbuk_sync_run_records', ['outcome' => 'subscription_created']);
        $this->assertDatabaseHas('sbuk_sync_run_records', ['outcome' => 'subscription_skipped']);
    }

    private function user(string $role = 'admin'): User
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test University',
            'slug' => 'university-'.Str::random(8),
            'company_name' => 'Test University',
            'email' => 'university@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        return User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role, 'status' => 'active']);
    }
}
