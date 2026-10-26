<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Monitoring\RrdToolService;
use App\Support\CustomerPortalSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = $request->user('customer')
            ->subscriptions()
            ->with(['plan', 'router'])
            ->latest()
            ->get();

        return view('customer.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'showBilling' => CustomerPortalSettings::showsBilling($request->user('customer')->tenant_id),
        ]);
    }

    public function show(Request $request, int $subscription): View
    {
        $subscription = $this->ownedSubscription($request, $subscription);
        $showBilling = CustomerPortalSettings::showsBilling($request->user('customer')->tenant_id);
        $subscription->load(['plan', 'accessPoint']);

        if ($showBilling) {
            $subscription->load(['invoices' => fn ($query) => $query->latest('issue_date')->limit(5)]);
        }

        return view('customer.subscriptions.show', compact('subscription', 'showBilling'));
    }

    public function bandwidthHistory(Request $request, int $subscription, RrdToolService $rrdTool): JsonResponse
    {
        $subscription = $this->ownedSubscription($request, $subscription);
        $range = in_array($request->query('range'), ['1h', '6h', '24h', '7d', '30d'], true)
            ? (string) $request->query('range')
            : '24h';

        try {
            $result = $rrdTool->subscriptionBandwidthChartData($subscription, $range);
        } catch (\Throwable) {
            $result = ['chartData' => [], 'hasData' => false];
        }

        return response()->json([...$result, 'range' => $range]);
    }

    private function ownedSubscription(Request $request, int $subscription): Subscription
    {
        return $request->user('customer')
            ->subscriptions()
            ->whereKey($subscription)
            ->firstOrFail();
    }
}
