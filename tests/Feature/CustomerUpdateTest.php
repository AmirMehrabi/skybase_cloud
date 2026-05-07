<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_customer_can_be_updated_without_individual_name_fields(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $customer = $this->createCustomer($tenant, [
            'customer_type' => 'business',
            'company_name' => 'Old Company',
            'name' => 'Old Company',
            'email' => 'old-company@example.com',
            'first_name' => null,
            'last_name' => null,
        ]);

        $response = $this->actingAs($user)->put(route('customers.update', $customer), [
            'customer_type' => 'business',
            'company_name' => 'New Company',
            'email' => 'new-company@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Business Road',
            'city' => 'Springfield',
            'country' => 'United States',
            'billing_type' => 'postpaid',
            'billing_enabled' => '1',
            'tax_exempt' => '0',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('customers.show', $customer));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'tenant_id' => $tenant->id,
            'customer_type' => 'business',
            'company_name' => 'New Company',
            'name' => 'New Company',
            'email' => 'new-company@example.com',
        ]);
    }

    public function test_individual_customer_update_requires_last_name(): void
    {
        $tenant = $this->createTenant('beta-net', 'BetaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $customer = $this->createCustomer($tenant);

        $response = $this->actingAs($user)->from(route('customers.edit', $customer))->put(route('customers.update', $customer), [
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'email' => 'jane.doe@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'billing_type' => 'postpaid',
            'billing_enabled' => '1',
            'tax_exempt' => '0',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('customers.edit', $customer));
        $response->assertSessionHasErrors('last_name');
    }

    private function createTenant(string $slug, string $companyName): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => $companyName,
            'slug' => $slug,
            'company_name' => $companyName,
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCustomer(Tenant $tenant, array $attributes = []): Customer
    {
        return Customer::query()->create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-'.Str::upper(Str::random(8)),
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'company_name' => null,
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
            'status' => 'active',
            ...$attributes,
        ]);
    }
}
