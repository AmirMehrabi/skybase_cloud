<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subscription\ExemptDataLimitRequest;
use App\Http\Requests\Subscription\GrantBonusDataRequest;
use App\Models\Subscription;
use App\Services\SubscriptionUsageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionUsageController extends Controller
{
    public function bonus(GrantBonusDataRequest $request, Subscription $subscription, SubscriptionUsageService $usage): RedirectResponse
    {
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
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $usage->reset($subscription, $validated['reason'], $request->user()?->id);

        return back()->with('success', 'Current cycle usage reset.');
    }

    public function exempt(ExemptDataLimitRequest $request, Subscription $subscription, SubscriptionUsageService $usage): RedirectResponse
    {
        $cycle = $usage->currentCycle($subscription);
        $cycle->update(['exempt_until' => $request->date('exempt_until')]);
        $usage->reconcile($subscription);

        return back()->with('success', 'Data-limit exemption applied.');
    }
}
