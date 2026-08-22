<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subscription\ExemptDataLimitRequest;
use App\Http\Requests\Subscription\GrantBonusDataRequest;
use App\Models\Subscription;
use App\Services\SubscriptionUsageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionUsageController extends Controller
{
    public function bonus(GrantBonusDataRequest $request, Subscription $subscription, SubscriptionUsageService $usage): RedirectResponse
    {
        $this->ensureFiniteDataAllowance($subscription);
        $validated = $request->validated();
        $bytes = match ($validated['unit']) {
            'MB' => $validated['amount'] * 1048576,
            'TB' => $validated['amount'] * 1099511627776,
            default => $validated['amount'] * 1073741824,
        };
        $usage->addData($subscription, 'bonus', $bytes, $validated['reason'], $request->user()?->id);

        return back()->with('success', 'Bonus data granted until the current cycle ends.');
    }

    public function reset(Request $request, Subscription $subscription, SubscriptionUsageService $usage): RedirectResponse
    {
        $this->ensureFiniteDataAllowance($subscription);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $currentCycle = $subscription->usageCycles()->whereNull('closed_at')->first();

        if (! $currentCycle || $currentCycle->usedBytes() === 0) {
            throw ValidationException::withMessages([
                'data_allowance' => 'There is no recorded cycle usage to reset.',
            ]);
        }

        $usage->reset($subscription, $validated['reason'], $request->user()?->id);

        return back()->with('success', 'Current cycle usage reset.');
    }

    public function exempt(ExemptDataLimitRequest $request, Subscription $subscription, SubscriptionUsageService $usage): RedirectResponse
    {
        $this->ensureFiniteDataAllowance($subscription);
        $cycle = $usage->currentCycle($subscription);
        $cycle->update(['exempt_until' => $request->date('exempt_until')]);
        $usage->reconcile($subscription);

        return back()->with('success', 'Data-limit exemption applied.');
    }

    private function ensureFiniteDataAllowance(Subscription $subscription): void
    {
        $subscription->loadMissing('plan');

        if (! $subscription->plan?->hasFiniteDataAllowance()) {
            throw ValidationException::withMessages([
                'data_allowance' => 'This subscription plan does not have a finite data allowance to manage.',
            ]);
        }
    }
}
