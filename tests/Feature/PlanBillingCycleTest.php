<?php

namespace Tests\Feature;

use App\Models\Subscription;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlanBillingCycleTest extends TestCase
{
    #[DataProvider('billingCycles')]
    public function test_all_plan_billing_cycles_have_exact_anniversary_boundaries(string $cycle, string $expectedEnd): void
    {
        $subscription = new Subscription(['billing_cycle' => $cycle]);

        $this->assertSame($expectedEnd, $subscription->billingPeriodEndFor(Carbon::parse('2026-08-19'))->toDateString());
    }

    public static function billingCycles(): array
    {
        return [
            'daily' => ['daily', '2026-08-19'],
            'weekly' => ['weekly', '2026-08-25'],
            'monthly' => ['monthly', '2026-09-18'],
            'quarterly' => ['quarterly', '2026-11-18'],
            'yearly' => ['yearly', '2027-08-18'],
        ];
    }
}
