<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_ticket_and_random_assignment_uses_active_team_agent(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant);
        $agent = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'support', 'status' => 'active']);
        $team = TicketTeam::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Network Operations',
            'slug' => 'network-operations',
            'status' => 'active',
            'assignment_strategy' => TicketTeam::STRATEGY_RANDOM,
            'first_response_minutes' => 120,
            'resolution_minutes' => 1440,
        ]);
        $team->users()->attach($agent->id, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'accepts_auto_assignment' => true,
        ]);

        $this->actingAs($customer, 'customer')
            ->post(route('customer.support.store'), [
                'ticket_team_id' => $team->id,
                'priority' => Ticket::PRIORITY_HIGH,
                'subject' => 'Internet is down',
                'message' => 'The service has been offline for ten minutes.',
            ])
            ->assertRedirect();

        $ticket = Ticket::withoutGlobalScopes()->firstOrFail();

        $this->assertSame($tenant->id, $ticket->tenant_id);
        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertSame($agent->id, $ticket->assigned_user_id);
        $this->assertSame(Ticket::STATUS_NEW, $ticket->status);
        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertDatabaseHas('ticket_messages', [
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
            'author_type' => 'customer',
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
        ]);
    }

    public function test_customer_cannot_create_ticket_for_another_customers_subscription(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant, ['email' => 'jane@example.com']);
        $otherCustomer = $this->createCustomer($tenant, ['email' => 'john@example.com']);
        $team = $this->createTeam($tenant);
        $subscription = $this->createSubscription($tenant, $otherCustomer);

        $this->actingAs($customer, 'customer')
            ->post(route('customer.support.store'), [
                'ticket_team_id' => $team->id,
                'subscription_id' => $subscription->id,
                'priority' => Ticket::PRIORITY_NORMAL,
                'subject' => 'Wrong subscription',
                'message' => 'This should fail.',
            ])
            ->assertSessionHasErrors('subscription_id');

        $this->assertSame(0, Ticket::withoutGlobalScopes()->count());
    }

    public function test_internal_notes_are_hidden_from_customer_portal(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $team = $this->createTeam($tenant);
        $ticket = $this->createTicket($tenant, $customer, $team);

        $this->actingAs($admin)
            ->post(route('support.tickets.reply', $ticket), [
                'visibility' => TicketMessage::VISIBILITY_INTERNAL,
                'body' => 'Check router logs before replying.',
            ])
            ->assertRedirect();

        $this->actingAs($customer, 'customer')
            ->get(route('customer.support.show', $ticket))
            ->assertOk()
            ->assertDontSee('Check router logs before replying.');
    }

    public function test_agent_cannot_view_ticket_outside_their_teams_or_assignment(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant);
        $agent = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'support', 'status' => 'active']);
        $ownTeam = $this->createTeam($tenant, 'General Support', 'general-support');
        $otherTeam = $this->createTeam($tenant, 'Billing', 'billing');
        $ownTeam->users()->attach($agent->id, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'accepts_auto_assignment' => true,
        ]);
        $ticket = $this->createTicket($tenant, $customer, $otherTeam);

        $this->actingAs($agent)
            ->get(route('support.tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_default_assignment_falls_back_to_queue_when_agent_is_not_team_member(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $defaultAgent = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'support', 'status' => 'active']);
        $team = TicketTeam::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Billing',
            'slug' => 'billing',
            'status' => 'active',
            'assignment_strategy' => TicketTeam::STRATEGY_DEFAULT_AGENT,
            'default_user_id' => $defaultAgent->id,
            'first_response_minutes' => 240,
            'resolution_minutes' => 2880,
        ]);
        $subscription = $this->createSubscription($tenant, $customer);

        $this->actingAs($admin)
            ->post(route('support.tickets.store'), [
                'subscription_id' => $subscription->id,
                'ticket_team_id' => $team->id,
                'priority' => Ticket::PRIORITY_NORMAL,
                'subject' => 'Invoice question',
                'message' => 'Please review this invoice.',
            ])
            ->assertRedirect();

        $this->assertNull(Ticket::withoutGlobalScopes()->firstOrFail()->assigned_user_id);
    }

    public function test_agent_can_search_ticket_subscription_by_customer_name_and_pppoe_username(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant, [
            'first_name' => 'Fatemeh',
            'last_name' => 'Karimi',
            'name' => 'Fatemeh Karimi',
        ]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $subscription = $this->createSubscription($tenant, $customer, ['pppoe_username' => 'fatemeh.karimi']);

        $this->actingAs($admin)
            ->get(route('support.tickets.create'))
            ->assertOk()
            ->assertSee('Fatemeh Karimi')
            ->assertSee('fatemeh.karimi')
            ->assertSee('Search by customer name or PPPoE username');

        $this->assertSame($customer->id, $subscription->customer_id);
    }

    public function test_agent_ticket_customer_is_derived_from_the_selected_subscription(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $subscriptionCustomer = $this->createCustomer($tenant, ['email' => 'subscription@example.com']);
        $unrelatedCustomer = $this->createCustomer($tenant, ['email' => 'unrelated@example.com']);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $team = $this->createTeam($tenant);
        $subscription = $this->createSubscription($tenant, $subscriptionCustomer, ['pppoe_username' => 'derived.user']);

        $this->actingAs($admin)
            ->post(route('support.tickets.store'), [
                'customer_id' => $unrelatedCustomer->id,
                'subscription_id' => $subscription->id,
                'ticket_team_id' => $team->id,
                'priority' => Ticket::PRIORITY_NORMAL,
                'subject' => 'Derived customer',
                'message' => 'Use the subscription owner.',
            ])
            ->assertRedirect();

        $ticket = Ticket::withoutGlobalScopes()->firstOrFail();

        $this->assertSame($subscriptionCustomer->id, $ticket->customer_id);
        $this->assertSame($subscription->id, $ticket->subscription_id);
    }

    public function test_admin_can_move_ticket_to_another_team_and_assign_a_specific_team_agent(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $currentTeam = $this->createTeam($tenant);
        $destinationTeam = $this->createTeam($tenant, 'Network Operations', 'network-operations');
        $destinationAgent = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'support', 'status' => 'active']);
        $destinationTeam->users()->attach($destinationAgent->id, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'accepts_auto_assignment' => false,
        ]);
        $ticket = $this->createTicket($tenant, $customer, $currentTeam);

        $this->actingAs($admin)
            ->patch(route('support.tickets.assign', $ticket), [
                'ticket_team_id' => $destinationTeam->id,
                'assigned_user_id' => $destinationAgent->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Ticket assignment updated.');

        $ticket->refresh();

        $this->assertSame($destinationTeam->id, $ticket->ticket_team_id);
        $this->assertSame($destinationAgent->id, $ticket->assigned_user_id);
        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'event_type' => 'ticket.team_changed',
        ]);
        $this->assertDatabaseHas('ticket_events', [
            'ticket_id' => $ticket->id,
            'event_type' => 'ticket.assigned',
        ]);
    }

    public function test_ticket_cannot_be_assigned_to_an_agent_outside_the_selected_team(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $team = $this->createTeam($tenant);
        $unrelatedAgent = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'support', 'status' => 'active']);
        $ticket = $this->createTicket($tenant, $customer, $team);

        $this->actingAs($admin)
            ->from(route('support.tickets.show', $ticket))
            ->patch(route('support.tickets.assign', $ticket), [
                'ticket_team_id' => $team->id,
                'assigned_user_id' => $unrelatedAgent->id,
            ])
            ->assertRedirect(route('support.tickets.show', $ticket))
            ->assertSessionHasErrors([
                'assigned_user_id' => 'The selected agent is not an active member of this team.',
            ]);

        $this->assertNull($ticket->fresh()->assigned_user_id);
    }

    public function test_ticket_cannot_be_created_with_an_agent_outside_the_selected_team(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $team = $this->createTeam($tenant);
        $unrelatedAgent = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'support', 'status' => 'active']);
        $subscription = $this->createSubscription($tenant, $customer);

        $this->actingAs($admin)
            ->post(route('support.tickets.store'), [
                'subscription_id' => $subscription->id,
                'ticket_team_id' => $team->id,
                'assigned_user_id' => $unrelatedAgent->id,
                'priority' => Ticket::PRIORITY_NORMAL,
                'subject' => 'Invalid assignment',
                'message' => 'Do not create this ticket.',
            ])
            ->assertSessionHasErrors([
                'assigned_user_id' => 'The selected agent is not an active member of this team.',
            ]);

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_ticket_page_displays_pppoe_username_and_available_team_agents(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $team = $this->createTeam($tenant);
        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'support',
            'status' => 'active',
            'name' => 'Network Agent',
        ]);
        $team->users()->attach($agent->id, [
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'accepts_auto_assignment' => false,
        ]);
        $subscription = $this->createSubscription($tenant, $customer, ['pppoe_username' => 'jane.pppoe']);
        $ticket = $this->createTicket($tenant, $customer, $team);
        $ticket->forceFill(['subscription_id' => $subscription->id])->save();

        $this->actingAs($admin)
            ->get(route('support.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('jane.pppoe')
            ->assertSee('Network Agent')
            ->assertSee('Update ownership');
    }

    public function test_admin_can_toggle_between_team_and_my_tickets(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $otherAgent = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'support', 'status' => 'active']);
        $team = $this->createTeam($tenant);
        $myTicket = $this->createTicket($tenant, $customer, $team, [
            'ticket_number' => 'TCK-260703-0001',
            'subject' => 'Assigned to administrator',
            'assigned_user_id' => $admin->id,
        ]);
        $teamTicket = $this->createTicket($tenant, $customer, $team, [
            'ticket_number' => 'TCK-260703-0002',
            'subject' => 'Assigned to another agent',
            'assigned_user_id' => $otherAgent->id,
        ]);

        $this->actingAs($admin)
            ->get(route('support.tickets.index', ['scope' => 'team']))
            ->assertOk()
            ->assertSee($myTicket->ticket_number)
            ->assertSee($teamTicket->ticket_number);

        $this->actingAs($admin)
            ->get(route('support.tickets.index', ['scope' => 'mine']))
            ->assertOk()
            ->assertSee($myTicket->ticket_number)
            ->assertDontSee($teamTicket->ticket_number);
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => str($slug)->headline()->toString(),
            'slug' => $slug,
            'company_name' => str($slug)->headline()->toString(),
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCustomer(Tenant $tenant, array $overrides = []): Customer
    {
        return Customer::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $tenant->id,
            'customer_code' => Customer::generateCustomerCode(),
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
            'password' => 'password123',
        ], $overrides));
    }

    private function createTeam(Tenant $tenant, string $name = 'General Support', string $slug = 'general-support'): TicketTeam
    {
        return TicketTeam::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'assignment_strategy' => TicketTeam::STRATEGY_QUEUE,
            'first_response_minutes' => 240,
            'resolution_minutes' => 2880,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSubscription(Tenant $tenant, Customer $customer, array $overrides = []): Subscription
    {
        $plan = Plan::factory()->create(['name' => 'Fiber 100']);

        return Subscription::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-'.Str::upper(Str::random(6)),
            'plan_id' => $plan->id,
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
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(Tenant $tenant, Customer $customer, TicketTeam $team, array $overrides = []): Ticket
    {
        return Ticket::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $tenant->id,
            'ticket_number' => 'TCK-260529-0001',
            'customer_id' => $customer->id,
            'ticket_team_id' => $team->id,
            'source' => 'customer_portal',
            'subject' => 'Need help',
            'priority' => Ticket::PRIORITY_NORMAL,
            'status' => Ticket::STATUS_NEW,
            'last_activity_at' => now(),
        ], $overrides));
    }
}
