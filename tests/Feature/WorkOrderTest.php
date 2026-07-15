<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TicketTeam;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkOrders\WorkOrderProvisioningService;
use App\Services\WorkOrders\WorkOrderTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_installation_can_be_created_without_subscription(): void
    {
        [$tenant, $admin, $customer] = $this->context('alpha');
        $team = $this->team($tenant);
        $team->users()->attach($admin->id, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'accepts_auto_assignment' => false,
        ]);

        $this->actingAs($admin)->post(route('work-orders.store'), [
            'customer_id' => $customer->id,
            'assigned_team_id' => $team->id,
            'assigned_user_id' => $admin->id,
            'type' => WorkOrderType::NewInstallation->value,
            'priority' => 'normal',
            'title' => 'Install residential fiber',
            'description' => 'Run drop and install customer equipment.',
            'service_address_line1' => '10 Main Street',
            'service_city' => 'Tehran',
            'contact_name' => 'Jane Doe',
            'contact_phone' => '555-0100',
        ])->assertRedirect();

        $workOrder = WorkOrder::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($tenant->id, $workOrder->tenant_id);
        $this->assertNull($workOrder->subscription_id);
        $this->assertSame($team->id, $workOrder->assigned_team_id);
        $this->assertSame($admin->id, $workOrder->assigned_user_id);
        $this->assertSame(WorkOrderStatus::Draft, $workOrder->status);
        $this->assertStringStartsWith('WO-', $workOrder->work_order_number);
        $this->assertSame(5, $workOrder->tasks()->count());
        $this->assertDatabaseHas('work_order_events', ['work_order_id' => $workOrder->id, 'event_type' => 'work_order.created']);
    }

    public function test_work_order_cannot_be_created_with_member_outside_selected_team(): void
    {
        [$tenant, $admin, $customer] = $this->context('alpha');
        $team = $this->team($tenant);
        $otherMember = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'support', 'status' => 'active']);

        $this->actingAs($admin)->post(route('work-orders.store'), [
            'customer_id' => $customer->id,
            'assigned_team_id' => $team->id,
            'assigned_user_id' => $otherMember->id,
            'type' => WorkOrderType::NewInstallation->value,
            'priority' => 'normal',
            'title' => 'Install residential fiber',
            'service_address_line1' => '10 Main Street',
        ])->assertSessionHasErrors([
            'assigned_user_id' => 'The selected member is not an active member of this team.',
        ]);

        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_non_admin_with_work_order_write_and_delete_permissions_can_create_and_delete(): void
    {
        [$tenant, , $customer] = $this->context('alpha');
        $role = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Work Order Operator',
            'permissions' => ['work_orders.read', 'work_orders.write', 'work_orders.delete'],
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role->name, 'status' => 'active']);
        $team = $this->team($tenant);
        $team->users()->attach($user->id, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'accepts_auto_assignment' => false,
        ]);

        $this->actingAs($user)->get(route('work-orders.create'))->assertOk();
        $this->actingAs($user)->post(route('work-orders.store'), [
            'customer_id' => $customer->id,
            'assigned_team_id' => $team->id,
            'type' => WorkOrderType::NewInstallation->value,
            'priority' => 'normal',
            'title' => 'Install residential fiber',
            'service_address_line1' => '10 Main Street',
        ])->assertRedirect();

        $workOrder = WorkOrder::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('work-orders.destroy', $workOrder))
            ->assertRedirect(route('work-orders.index'));

        $this->assertSoftDeleted('work_orders', ['id' => $workOrder->id]);
    }

    public function test_admin_can_render_work_order_queue_and_detail(): void
    {
        [$tenant, $admin, $customer] = $this->context('alpha');
        $workOrder = $this->workOrder($tenant, $admin, $customer);

        $this->actingAs($admin)
            ->get(route('work-orders.index'))
            ->assertOk()
            ->assertSee($workOrder->work_order_number);

        $this->actingAs($admin)
            ->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Checklist')
            ->assertSee('Customer and service');
    }

    public function test_create_form_only_exposes_subscriptions_for_the_selected_customer(): void
    {
        [$tenant, $admin, $customer] = $this->context('alpha');
        $subscription = Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-ALPHA1',
            'connection_type' => 'dhcp',
            'base_price' => 100,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 100,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now(),
            'pppoe_username' => 'jane.fiber',
        ]);

        $response = $this->actingAs($admin)->get(route('work-orders.create'));

        $response->assertOk()
            ->assertSee('subscriptionsByCustomer')
            ->assertSee('jane.fiber')
            ->assertDontSee('>jane.fiber</option>', false);
    }

    public function test_subscription_required_work_type_is_rejected_without_subscription(): void
    {
        [, $admin, $customer] = $this->context('alpha');

        $this->actingAs($admin)->post(route('work-orders.store'), [
            'customer_id' => $customer->id,
            'type' => WorkOrderType::Repair->value,
            'priority' => 'high',
            'title' => 'Repair service',
            'service_address_line1' => '10 Main Street',
        ])->assertSessionHasErrors('subscription_id');

        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_customer_from_another_tenant_is_rejected(): void
    {
        [, $admin] = $this->context('alpha');
        [, , $otherCustomer] = $this->context('beta');

        $this->actingAs($admin)->post(route('work-orders.store'), [
            'customer_id' => $otherCustomer->id,
            'type' => WorkOrderType::NewInstallation->value,
            'priority' => 'normal',
            'title' => 'Cross tenant attempt',
            'service_address_line1' => '10 Main Street',
        ])->assertSessionHasErrors('customer_id');
    }

    public function test_invalid_transition_is_rejected_and_valid_transition_is_audited(): void
    {
        [$tenant, $admin, $customer] = $this->context('alpha');
        $workOrder = $this->workOrder($tenant, $admin, $customer);
        $service = app(WorkOrderTransitionService::class);

        try {
            $service->transition($workOrder, WorkOrderStatus::Completed, $admin, ['completion_notes' => 'Done']);
            $this->fail('The invalid transition should throw.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $service->transition($workOrder, WorkOrderStatus::Submitted, $admin);
        $this->assertSame(WorkOrderStatus::Submitted, $workOrder->refresh()->status);
        $this->assertDatabaseHas('work_order_events', ['work_order_id' => $workOrder->id, 'event_type' => 'work_order.status_changed']);
    }

    public function test_ready_installation_is_provisioned_idempotently(): void
    {
        Bus::fake();
        [$tenant, $admin, $customer] = $this->context('alpha');
        $plan = Plan::factory()->create(['status' => 'active', 'billing_cycle' => 'monthly', 'price' => 100]);
        $router = Router::factory()->create(['tenant_id' => $tenant->id]);
        $workOrder = $this->workOrder($tenant, $admin, $customer);
        $workOrder->forceFill(['type' => WorkOrderType::NewInstallation, 'status' => WorkOrderStatus::ReadyForActivation])->save();
        $data = [
            'name' => 'Jane Fiber',
            'service_type' => 'pppoe',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'jane.fiber',
            'pppoe_password' => 'secret123',
            'ip_management' => 'router',
        ];

        $first = app(WorkOrderProvisioningService::class)->provision($workOrder, $admin, $data);
        $second = app(WorkOrderProvisioningService::class)->provision($workOrder->refresh(), $admin, $data);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('active', $first->status);
        $this->assertSame($first->id, $workOrder->refresh()->subscription_id);
        $this->assertSame(1, Subscription::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }

    /** @return array{Tenant, User, Customer} */
    private function context(string $slug): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(), 'name' => ucfirst($slug), 'slug' => $slug,
            'company_name' => ucfirst($slug), 'email' => "{$slug}@example.com", 'timezone' => 'UTC', 'status' => 'active',
        ]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'customer_code' => 'CUS-'.strtoupper($slug), 'customer_type' => 'individual',
            'first_name' => 'Jane', 'last_name' => ucfirst($slug), 'name' => 'Jane '.ucfirst($slug),
            'email' => "customer-{$slug}@example.com", 'mobile' => '555-0100', 'address_line1' => '10 Main Street',
            'city' => 'Tehran', 'country' => 'Iran', 'status' => 'active', 'billing_type' => 'postpaid',
            'billing_enabled' => true, 'balance' => 0, 'credit_limit' => 0, 'tax_exempt' => false, 'password' => 'password123',
        ]);

        return [$tenant, $admin, $customer];
    }

    private function workOrder(Tenant $tenant, User $admin, Customer $customer): WorkOrder
    {
        return WorkOrder::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'work_order_number' => 'WO-260706-0001',
            'customer_id' => $customer->id, 'created_by_user_id' => $admin->id,
            'type' => WorkOrderType::Other, 'priority' => 'normal', 'status' => WorkOrderStatus::Draft,
            'title' => 'Test work', 'service_address_line1' => '10 Main Street',
        ]);
    }

    private function team(Tenant $tenant): TicketTeam
    {
        return TicketTeam::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Installations',
            'slug' => 'installations',
            'status' => 'active',
            'assignment_strategy' => TicketTeam::STRATEGY_QUEUE,
            'first_response_minutes' => 240,
            'resolution_minutes' => 2880,
        ]);
    }
}
