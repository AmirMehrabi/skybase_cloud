<?php

namespace App\Support\UserGroups;

use App\Models\Customer;
use App\Models\User;

class UserGroupContext
{
    public function shouldScope(): bool
    {
        return $this->staffUser() !== null && ! $this->bypassesScope();
    }

    public function groupId(): ?int
    {
        $groupId = $this->principal()?->user_group_id;

        return $groupId === null ? null : (int) $groupId;
    }

    public function tenantId(): ?string
    {
        $tenantId = tenant_id() ?? $this->principal()?->tenant_id;

        return $tenantId === null ? null : (string) $tenantId;
    }

    public function bypassesScope(): bool
    {
        $user = $this->staffUser();

        return $user !== null && ($user->isOwner() || $user->isCentralAdmin());
    }

    private function principal(): User|Customer|null
    {
        return $this->staffUser() ?? $this->customerUser();
    }

    private function staffUser(): ?User
    {
        $guard = auth('web');
        $user = method_exists($guard, 'hasUser') && $guard->hasUser() ? $guard->user() : null;

        return $user instanceof User ? $user : null;
    }

    private function customerUser(): ?Customer
    {
        $guard = auth('customer');
        $user = method_exists($guard, 'hasUser') && $guard->hasUser() ? $guard->user() : null;

        return $user instanceof Customer ? $user : null;
    }
}
