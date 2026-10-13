<?php

namespace App\Support\UserGroups;

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

    private function principal(): mixed
    {
        return $this->staffUser() ?? $this->customerUser();
    }

    private function staffUser(): mixed
    {
        $guard = auth('web');

        return method_exists($guard, 'hasUser') && $guard->hasUser() ? $guard->user() : null;
    }

    private function customerUser(): mixed
    {
        $guard = auth('customer');

        return method_exists($guard, 'hasUser') && $guard->hasUser() ? $guard->user() : null;
    }
}
