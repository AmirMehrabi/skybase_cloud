<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionIpLease;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SubscriptionIpLease> */
class SubscriptionIpLeaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ip_address' => fake()->localIpv4(),
            'token' => (string) Str::uuid(),
            'session_key' => hash('sha256', (string) Str::uuid()),
            'last_seen_at' => now(),
            'pending_until' => now()->addMinutes(2),
        ];
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (): array => [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'router_id' => $subscription->router_id,
        ]);
    }
}
