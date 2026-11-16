<?php

namespace Tests\Feature;

use App\Jobs\ProcessGolestanSync;
use App\Models\Customer;
use App\Models\GolestanSyncRun;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GolestanSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Plan $plan;

    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.golestan.enabled' => true, 'services.golestan.username' => 'test-user', 'services.golestan.password' => 'test-password']);
        $this->admin = $this->adminForNewTenant();
        $this->plan = Plan::factory()->create(['tenant_id' => $this->admin->tenant_id, 'status' => 'active']);
        $this->router = Router::factory()->create(['tenant_id' => $this->admin->tenant_id]);
        $this->actingAs($this->admin);
    }

    public function test_new_and_repeat_syncs_use_the_searched_results_and_keep_one_subscription(): void
    {
        $token = $this->search();
        $preview = $this->preview($token)->assertOk()->assertJsonPath('ready', 1);
        $start = $this->postJson(route('golestan.sync.start'), ['preview_token' => $preview->json('preview_token')])->assertOk();
        $this->getJson($start->json('status_url'))->assertOk()->assertJsonPath('status', 'completed')
            ->assertJsonPath('outcomes.0.message', 'Created customer. Created subscription.');
        $this->postJson(route('golestan.sync.start'), ['preview_token' => $preview->json('preview_token')])
            ->assertOk()->assertJsonPath('status_url', $start->json('status_url'));
        $customer = Customer::query()->sole();
        $subscription = Subscription::query()->sole();
        $this->assertSame('0012345678', $customer->national_id);
        $this->assertNull($customer->mobile);
        $this->assertSame('pending', $subscription->status);
        $this->assertSame('pppoe', $subscription->connection_type);
        $this->assertSame('00123', $subscription->pppoe_username);
        $this->assertSame('0012345678', $subscription->pppoe_password);
        $this->assertFalse($subscription->billing_enabled);

        $replacement = Plan::factory()->create(['tenant_id' => $this->admin->tenant_id, 'status' => 'active']);
        $this->confirm($this->preview($token, ['plan_id' => $replacement->id])->assertOk()->json('preview_token'));
        $this->assertSame($replacement->id, $subscription->fresh()->plan_id);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseCount('golestan_student_links', 1);
        Http::assertSentCount(1);
    }

    public function test_existing_subscription_requires_an_explicit_choice_and_empty_fields_are_preserved(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->admin->tenant_id, 'customer_type' => 'individual', 'national_id' => '0012345678',
            'last_name' => 'Existing', 'mobile' => '09123456789',
        ]);
        $subscription = Subscription::factory()->create(['tenant_id' => $this->admin->tenant_id, 'customer_id' => $customer->id, 'status' => 'pending']);
        $token = $this->search('<Student STDNO="00123" firstname="Updated" SSNO="0012345678" />');
        $this->preview($token)->assertOk()->assertJsonPath('rows.0.action', 'choose')->assertJsonPath('preview_token', null);
        $preview = $this->preview($token, ['subscriptions' => [0 => $subscription->id]])->assertOk();
        $this->confirm($preview->json('preview_token'));

        $this->assertSame('Updated', $customer->fresh()->first_name);
        $this->assertSame('Existing', $customer->fresh()->last_name);
        $this->assertSame('09123456789', $customer->fresh()->mobile);
        $this->assertDatabaseHas('golestan_student_links', ['student_number' => '00123', 'subscription_id' => $subscription->id]);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_foreign_tokens_and_options_are_rejected_and_previews_expire(): void
    {
        $token = $this->search();
        $other = $this->adminForNewTenant();
        $foreignPlan = Plan::factory()->create(['tenant_id' => $other->tenant_id, 'status' => 'active']);
        $this->preview($token, ['plan_id' => $foreignPlan->id])->assertUnprocessable();
        $this->actingAs($other);
        $this->preview($token)->assertForbidden();
        $this->actingAs($this->admin);
        $this->travel(21)->minutes();
        $this->preview($token)->assertUnprocessable();
    }

    public function test_ip_capacity_is_previewed_and_stale_address_fails_only_its_student(): void
    {
        Queue::fake();
        $pool = IpPool::factory()->active()->create(['tenant_id' => $this->admin->tenant_id, 'router_id' => $this->router->id]);
        $options = ['ip_management' => 'system', 'ip_pool_id' => $pool->id, 'rows' => [0, 1]];
        $token = $this->search('<Student STDNO="00123" firstname="First" name="Student" SSNO="0012345678"/><Student STDNO="00124" firstname="Second" name="Student" SSNO="0012345679"/>');
        $this->preview($token, $options)->assertOk()->assertJsonPath('preview_token', null)->assertJsonPath('needed', 2);
        foreach (['192.0.2.10', '192.0.2.11'] as $address) {
            IpAddress::factory()->create(['tenant_id' => $this->admin->tenant_id, 'ip_pool_id' => $pool->id, 'ip_address' => $address, 'status' => 'available']);
        }
        $preview = $this->preview($token, $options + ['addresses' => ['192.0.2.10', '192.0.2.11']])->assertOk();
        $start = $this->postJson(route('golestan.sync.start'), ['preview_token' => $preview->json('preview_token')])->assertOk();
        IpAddress::query()->where('tenant_id', $this->admin->tenant_id)->where('ip_address', '192.0.2.10')->update(['status' => 'reserved']);
        $run = GolestanSyncRun::query()->sole();
        $job = new ProcessGolestanSync($this->admin->tenant_id, $run->id);
        app()->call($job->handle(...));
        app()->call($job->handle(...));
        $this->getJson($start->json('status_url'))->assertOk()->assertJsonPath('status', 'completed_with_errors')
            ->assertJsonPath('outcomes.0.status', 'failed')->assertJsonPath('outcomes.1.status', 'synced');
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseHas('subscriptions', ['ip_address' => '192.0.2.11']);
    }

    public function test_conflicted_students_are_skipped_and_listed_in_the_completed_run(): void
    {
        Customer::factory()->count(2)->create([
            'tenant_id' => $this->admin->tenant_id, 'customer_type' => 'individual', 'national_id' => '۰۰۱۲۳۴۵۶۷۸',
        ]);
        $token = $this->search('<Student STDNO="00123" firstname="First" name="Student" SSNO="0012345678"/><Student STDNO="00124" firstname="Second" name="Student" SSNO="0012345679"/>');
        $preview = $this->preview($token, ['rows' => [0, 1]])->assertOk()
            ->assertJsonPath('rows.0.action', 'skip')->assertJsonPath('ready', 1);
        $start = $this->postJson(route('golestan.sync.start'), ['preview_token' => $preview->json('preview_token')])->assertOk();
        $this->getJson($start->json('status_url'))->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('outcomes.0.status', 'skipped')->assertJsonPath('outcomes.1.status', 'synced');
        $this->assertDatabaseCount('customers', 3);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_custom_mapping_updates_linked_credentials_without_exposing_the_password(): void
    {
        config(['security.encrypt_secrets' => true]);
        $token = $this->search('<Student STDNO="00123" firstname="First" name="Student" SSNO="0012345678" Login="student-123" AccessCode="alternate-secret"/>');
        $this->confirm($this->preview($token)->assertOk()->json('preview_token'));
        $mapping = ['username' => 'Login', 'password' => 'AccessCode'];
        $preview = $this->preview($token, ['credential_fields' => $mapping])->assertOk()
            ->assertJsonPath('rows.0.credentials_changed', true)->assertDontSee('alternate-secret');
        $this->confirm($preview->json('preview_token'));

        $subscription = Subscription::query()->sole();
        $this->assertSame('student-123', $subscription->pppoe_username);
        $this->assertSame('alternate-secret', $subscription->pppoe_password);
        $this->assertNotSame('alternate-secret', $subscription->getRawOriginal('pppoe_password'));
        $this->preview($token, ['credential_fields' => $mapping])->assertOk()->assertJsonPath('rows.0.credentials_changed', false);
    }

    public function test_missing_colliding_and_unavailable_credential_fields_are_rejected(): void
    {
        $token = $this->search('<Student STDNO="00123" firstname="First" name="Student" Login="shared"/><Student STDNO="00124" firstname="Second" name="Student" Login="Shared"/>');
        $this->preview($token)->assertOk()->assertJsonPath('rows.0.message', 'The mapped PPPoE password is missing or invalid.');
        $this->preview($token, ['credential_fields' => ['username' => 'Login', 'password' => 'Unknown']])->assertUnprocessable();
        $this->preview($token, ['credential_fields' => []])->assertUnprocessable();
        $mapping = ['username' => 'Login', 'password' => 'STDNO'];
        $this->preview($token, ['rows' => [0, 1], 'credential_fields' => $mapping])->assertOk()
            ->assertJsonPath('ready', 0)->assertJsonPath('rows.0.message', 'More than one selected student maps to this PPPoE username.');

        Subscription::factory()->create(['tenant_id' => $this->admin->tenant_id, 'pppoe_username' => 'SHARED']);
        $this->preview($token, ['credential_fields' => $mapping])->assertOk()
            ->assertJsonPath('rows.0.message', 'The mapped PPPoE username is already used by another subscription.');
    }

    private function preview(string $token, array $overrides = []): TestResponse
    {
        return $this->postJson(route('golestan.sync.preview'), $overrides + [
            'search_token' => $token, 'rows' => [0], 'plan_id' => $this->plan->id,
            'router_id' => $this->router->id, 'ip_management' => 'router',
        ]);
    }

    private function confirm(string $token): void
    {
        $start = $this->postJson(route('golestan.sync.start'), ['preview_token' => $token])->assertOk();
        $this->getJson($start->json('status_url'))->assertOk()->assertJsonPath('status', 'completed');
    }

    private function search(string $students = '<Student STDNO="00123" firstname="First" name="Student" SSNO="۰۰۱۲۳۴۵۶۷۸" />'): string
    {
        Http::fake(['*' => Http::response('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><golInfoResponse xmlns="urn:nowpardaz"><golInfoResult><Root>'.$students.'</Root></golInfoResult></golInfoResponse></soap:Body></soap:Envelope>')]);

        return $this->post(route('golestan.search'), ['student_number' => '00123', 'student_number_to' => '00124'])->assertOk()->viewData('searchToken');
    }

    private function adminForNewTenant(): User
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(), 'name' => 'Test University', 'slug' => 'university-'.Str::random(8),
            'company_name' => 'Test University', 'email' => 'university@example.com', 'timezone' => 'UTC', 'status' => 'active',
        ]);

        return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);
    }
}
