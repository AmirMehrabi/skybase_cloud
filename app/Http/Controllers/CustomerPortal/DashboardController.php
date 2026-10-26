<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\CustomerBandwidthUsageService;
use App\Support\CustomerPortalSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = $request->user('customer');
        $showBilling = CustomerPortalSettings::showsBilling($customer->tenant_id);

        $subscriptionsQuery = $customer->subscriptions();
        $subscriptions = (clone $subscriptionsQuery)
            ->with('plan')
            ->latest()
            ->limit(5)
            ->get();

        $invoices = $showBilling
            ? $customer->invoices()->latest('issue_date')->limit(5)->get()
            : collect();

        return view('customer.dashboard', [
            'customer' => $customer,
            'showBilling' => $showBilling,
            'subscriptions' => $subscriptions,
            'invoices' => $invoices,
            'stats' => [
                'total_subscriptions' => (clone $subscriptionsQuery)->count(),
                'active_subscriptions' => (clone $subscriptionsQuery)->active()->count(),
                'suspended_subscriptions' => (clone $subscriptionsQuery)->suspended()->count(),
                'online_subscriptions' => (clone $subscriptionsQuery)->where('connection_status', 'online')->count(),
                'open_tickets' => $customer->tickets()->open()->count(),
                'unpaid_invoices' => $showBilling ? $customer->invoices()->outstanding()->count() : null,
                'current_balance' => $showBilling ? $customer->balance : null,
            ],
            'nextBillingDate' => $showBilling ? $customer->subscriptions()
                ->whereNotNull('next_billing_date')
                ->whereDate('next_billing_date', '>=', today())
                ->min('next_billing_date') : null,
        ]);
    }

    public function usage(Request $request, CustomerBandwidthUsageService $usageService): JsonResponse
    {
        $range = in_array($request->query('range'), ['1h', '6h', '24h', '7d', '30d'], true)
            ? (string) $request->query('range')
            : '24h';
        $result = $usageService->aggregate(
            $request->user('customer')->subscriptions()->get(),
            $range
        );

        return response()->json([...$result, 'range' => $range]);
    }
}
