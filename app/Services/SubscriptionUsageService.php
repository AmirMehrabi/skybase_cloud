<?php

namespace App\Services;

use App\Models\RadiusAccountingRecord;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionDataAdjustment;
use App\Models\SubscriptionRestriction;
use App\Models\SubscriptionUsageCycle;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionUsageService
{
    public function __construct(private readonly SubscriptionRestrictionService $restrictions) {}

    public function currentCycle(Subscription $subscription): SubscriptionUsageCycle
    {
        $existing = SubscriptionUsageCycle::withoutGlobalScopes()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('subscription_id', $subscription->id)
            ->whereNull('closed_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $subscription->loadMissing('plan');
        $start = Carbon::parse($subscription->start_date ?? $subscription->activation_date ?? now())->startOfDay();
        while ($subscription->nextBillingBoundaryFor($start)->lte(now())) {
            $start = $subscription->nextBillingBoundaryFor($start)->startOfDay();
        }

        return SubscriptionUsageCycle::withoutGlobalScopes()->create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'starts_at' => $start,
            'ends_at' => $subscription->nextBillingBoundaryFor($start)->subSecond(),
            'allowance_bytes' => $subscription->plan?->dataLimitBytes(),
        ]);
    }

    public function reconcile(Subscription $subscription): SubscriptionUsageCycle
    {
        $cycle = $this->currentCycle($subscription);
        $usageQuery = DB::table('radacct')
            ->where('username', $subscription->pppoe_username)
            ->where(function ($query) use ($cycle): void {
                $query->whereBetween('acctstarttime', [$cycle->starts_at, $cycle->ends_at])
                    ->orWhere(function ($query) use ($cycle): void {
                        $query->where('acctstarttime', '<', $cycle->starts_at)
                            ->where(function ($query) use ($cycle): void {
                                $query->whereNull('acctstoptime')->orWhere('acctstoptime', '>=', $cycle->starts_at);
                            });
                    });
            })
            ->selectRaw('COALESCE(SUM(acctinputoctets), 0) as upload, COALESCE(SUM(acctoutputoctets), 0) as download');
        if ($cycle->accounting_baselines !== null) {
            $upload = 0;
            $download = 0;
            foreach ($this->accountingForSubscription($subscription)
                ->where('acctstarttime', '<=', $cycle->ends_at)
                ->where(fn (Builder $query) => $query->whereNull('acctstoptime')->orWhere('acctstoptime', '>=', $cycle->starts_at))
                ->get() as $session) {
                $baseline = $cycle->accounting_baselines[$session->getKey()] ?? ['upload' => 0, 'download' => 0];
                $upload += max(0, $session->acctinputoctets - $baseline['upload']);
                $download += max(0, $session->acctoutputoctets - $baseline['download']);
            }
            $usage = (object) compact('upload', 'download');
        } else {
            $usage = $usageQuery->first();
        }

        $cycle->update([
            'used_upload_bytes' => max(0, (int) $usage->upload),
            'used_download_bytes' => max(0, (int) $usage->download),
        ]);
        $cycle->refresh();
        $allowance = $cycle->effectiveAllowanceBytes();

        if (! $cycle->isExempt() && $allowance !== null && $cycle->usedBytes() >= $allowance) {
            $cycle->update(['quota_reached_at' => $cycle->quota_reached_at ?? now()]);
            $this->restrictions->restrict($subscription, 'quota', 'The subscription reached its cycle data allowance.', ['usage_cycle_id' => $cycle->id]);
        } else {
            $cycle->update(['quota_reached_at' => null]);
            $this->restrictions->clear($subscription, 'quota', 'The subscription has available cycle data.');
        }

        return $cycle->fresh();
    }

    public function rollover(SubscriptionUsageCycle $cycle): SubscriptionUsageCycle
    {
        $subscription = $cycle->subscription()->withoutGlobalScopes()->with('plan')->firstOrFail();
        $cycle->adjustments()->where('status', 'active')->update(['status' => 'expired']);
        $cycle->update(['closed_at' => now()]);
        $this->restrictions->clear($subscription, 'quota', 'A new data usage cycle began.');

        return SubscriptionUsageCycle::withoutGlobalScopes()->create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'starts_at' => $cycle->ends_at->copy()->addSecond(),
            'ends_at' => $subscription->nextBillingBoundaryFor($cycle->ends_at->copy()->addSecond())->subSecond(),
            'allowance_bytes' => $subscription->plan?->dataLimitBytes(),
        ]);
    }

    public function restart(Subscription $subscription, CarbonInterface $startsAt, CarbonInterface $endsAt, int $userId): SubscriptionUsageCycle
    {
        $baselines = [];
        foreach ($this->accountingForSubscription($subscription)->where('acctstarttime', '<=', $startsAt)
            ->where(fn (Builder $query) => $query->whereNull('acctstoptime')->orWhere('acctstoptime', '>=', $startsAt))
            ->get() as $session) {
            $baselines[$session->getKey()] = ['upload' => (int) $session->acctinputoctets, 'download' => (int) $session->acctoutputoctets];
        }

        foreach ($subscription->usageCycles()->where('tenant_id', $subscription->tenant_id)->whereNull('closed_at')->get() as $cycle) {
            $cycle->adjustments()->where('tenant_id', $subscription->tenant_id)->where('status', 'active')->update(['status' => 'expired']);
            $cycle->update([
                'closed_at' => $startsAt,
                'ends_at' => $cycle->ends_at->lt($startsAt) ? $cycle->ends_at : $startsAt->copy()->subSecond(),
            ]);
        }

        SubscriptionRestriction::query()->where('tenant_id', $subscription->tenant_id)->where('subscription_id', $subscription->id)
            ->where('type', 'quota')->whereNull('cleared_at')->update([
                'cleared_at' => $startsAt,
                'cleared_by' => $userId,
            ]);

        return $subscription->usageCycles()->create([
            'tenant_id' => $subscription->tenant_id,
            'plan_id' => $subscription->plan_id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'allowance_bytes' => $subscription->plan?->dataLimitBytes(),
            'accounting_baselines' => $baselines,
        ]);
    }

    private function accountingForSubscription(Subscription $subscription): Builder
    {
        return RadiusAccountingRecord::withoutGlobalScopes()->whereNotNull('username')->where('username', $subscription->pppoe_username)
            ->whereIn('nasipaddress', Router::withoutGlobalScopes()->where('tenant_id', $subscription->tenant_id)->select('ip_address'));
    }

    public function addData(Subscription $subscription, string $type, int $bytes, string $reason, ?int $userId = null): SubscriptionDataAdjustment
    {
        $cycle = $this->currentCycle($subscription);
        $adjustment = $cycle->adjustments()->create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'type' => $type,
            'bytes' => $bytes,
            'reason' => $reason,
            'expires_at' => $cycle->ends_at,
            'created_by' => $userId,
        ]);
        $this->reconcile($subscription);

        return $adjustment;
    }

    public function reset(Subscription $subscription, string $reason, ?int $userId = null): SubscriptionDataAdjustment
    {
        $cycle = $this->currentCycle($subscription);

        return $this->addData($subscription, 'reset', $cycle->usedBytes(), $reason, $userId);
    }
}
