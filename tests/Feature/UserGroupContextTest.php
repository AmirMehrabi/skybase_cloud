<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Support\UserGroups\UserGroupContext;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserGroupContextTest extends TestCase
{
    public function test_guests_have_no_user_group_context(): void
    {
        $context = app(UserGroupContext::class);

        $this->assertFalse($context->shouldScope());
        $this->assertFalse($context->bypassesScope());
        $this->assertNull($context->groupId());
        $this->assertNull($context->tenantId());
    }

    #[DataProvider('customerGuardConfigurations')]
    public function test_customers_are_never_treated_as_staff(bool $populateWebGuard): void
    {
        $customer = new Customer(['tenant_id' => 'customer-tenant', 'user_group_id' => 12]);
        $this->actingAs($customer, 'customer');

        if ($populateWebGuard) {
            auth('web')->setUser($customer);
        }

        $context = app(UserGroupContext::class);

        $this->assertFalse($context->bypassesScope());
        $this->assertFalse($context->shouldScope());
        $this->assertSame(12, $context->groupId());
        $this->assertSame('customer-tenant', $context->tenantId());
    }

    public static function customerGuardConfigurations(): array
    {
        return [
            'customer guard only' => [false],
            'customer also present in web guard' => [true],
        ];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_scope_depends_on_the_staff_role(string $role, ?string $tenantId, bool $bypassesScope): void
    {
        $user = User::factory()->make([
            'tenant_id' => $tenantId,
            'user_group_id' => 34,
            'role' => $role,
        ]);
        $this->actingAs($user, 'web');

        $context = app(UserGroupContext::class);

        $this->assertSame($bypassesScope, $context->bypassesScope());
        $this->assertSame(! $bypassesScope, $context->shouldScope());
        $this->assertSame(34, $context->groupId());
        $this->assertSame($tenantId, $context->tenantId());
    }

    public static function staffRoles(): array
    {
        return [
            'owner' => ['owner', 'staff-tenant', true],
            'central administrator' => ['super_admin', null, true],
            'tenant administrator' => ['admin', 'staff-tenant', false],
            'tenant super administrator' => ['super_admin', 'staff-tenant', false],
        ];
    }
}
