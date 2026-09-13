<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerMultipleOrganizationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_created_with_multiple_organizations(): void
    {
        [$tenant, $user, $firstOrganization, $secondOrganization] = $this->dependencies();

        $this->actingAs($user)
            ->post(route('customers.store'), $this->customerPayload([
                $firstOrganization->id,
                $secondOrganization->id,
            ]))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $customer = Customer::query()->where('email', 'multi@example.com')->firstOrFail();

        $this->assertSame($firstOrganization->id, $customer->organization_id);
        $this->assertEqualsCanonicalizing(
            [$firstOrganization->id, $secondOrganization->id],
            $customer->organizations()->pluck('organizations.id')->all(),
        );
        $this->assertDatabaseHas('customer_organization', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'organization_id' => $secondOrganization->id,
        ]);
    }

    public function test_customer_organizations_can_be_replaced_when_editing(): void
    {
        [$tenant, $user, $firstOrganization, $secondOrganization] = $this->dependencies();
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $firstOrganization->id,
            'customer_code' => 'CUST-MULTI-EDIT',
            'customer_type' => 'individual',
            'first_name' => 'Multi',
            'last_name' => 'Customer',
            'name' => 'Multi Customer',
            'email' => 'multi@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
        ]);

        $this->actingAs($user)
            ->put(route('customers.update', $customer), [
                ...$this->customerPayload([$secondOrganization->id]),
                'status' => 'active',
            ])
            ->assertRedirect(route('customers.show', $customer))
            ->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertSame($secondOrganization->id, $customer->organization_id);
        $this->assertSame(
            [$secondOrganization->id],
            $customer->organizations()->pluck('organizations.id')->all(),
        );
        $this->assertDatabaseMissing('customer_organization', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'organization_id' => $firstOrganization->id,
        ]);
    }

    public function test_customer_forms_use_the_searchable_organization_selector(): void
    {
        [$tenant, $user, $firstOrganization] = $this->dependencies();
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $firstOrganization->id,
            'customer_code' => 'CUST-MULTI-FORM',
            'customer_type' => 'individual',
            'first_name' => 'Form',
            'last_name' => 'Customer',
            'name' => 'Form Customer',
            'email' => 'form@example.com',
            'status' => 'active',
            'billing_enabled' => true,
        ]);

        $this->actingAs($user)
            ->get(route('customers.create'))
            ->assertOk()
            ->assertSee('Search organizations...')
            ->assertSee('organization_ids[]', false);

        $this->actingAs($user)
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('Search organizations...')
            ->assertSee('organization_ids[]', false);
    }

    public function test_customer_cannot_be_attached_to_another_tenants_organization(): void
    {
        [, $user] = $this->dependencies();
        $otherTenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'company_name' => 'Other Tenant',
            'email' => 'other-tenant@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
        $otherOrganization = Organization::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Organization',
            'code' => 'ORG-OTHER',
            'status' => 'active',
            'billing_enabled' => false,
        ]);

        $this->actingAs($user)
            ->postJson(route('customers.store'), $this->customerPayload([$otherOrganization->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['organization_ids.0']);

        $this->assertDatabaseMissing('customers', ['email' => 'multi@example.com']);
    }

    /**
     * @return array{Tenant, User, Organization, Organization}
     */
    private function dependencies(): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Customer Organizations',
            'slug' => 'customer-organizations',
            'company_name' => 'Customer Organizations',
            'email' => 'customer-organizations@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $firstOrganization = Organization::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'First Organization',
            'status' => 'active',
            'billing_enabled' => false,
        ]);
        $secondOrganization = Organization::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Second Organization',
            'status' => 'active',
            'billing_enabled' => false,
        ]);

        return [$tenant, $user, $firstOrganization, $secondOrganization];
    }

    /**
     * @param  list<int>  $organizationIds
     * @return array<string, mixed>
     */
    private function customerPayload(array $organizationIds): array
    {
        return [
            'organization_ids' => $organizationIds,
            'customer_type' => 'individual',
            'first_name' => 'Multi',
            'last_name' => 'Customer',
            'email' => 'multi@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'billing_type' => 'postpaid',
            'billing_enabled' => '1',
            'tax_exempt' => '0',
        ];
    }
}
