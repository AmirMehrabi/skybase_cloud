<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = $request->user('customer');

        $subscriptions = Subscription::query()
            ->where('customer_id', $customer->id)
            ->with('plan')
            ->latest()
            ->limit(5)
            ->get();

        $invoices = Invoice::query()
            ->where('customer_id', $customer->id)
            ->latest('issue_date')
            ->limit(5)
            ->get();

        return view('customer.dashboard', [
            'customer' => $customer,
            'subscriptions' => $subscriptions,
            'invoices' => $invoices,
            'stats' => [
                'active_subscriptions' => Subscription::query()->where('customer_id', $customer->id)->active()->count(),
                'open_tickets' => 0,
                'unpaid_invoices' => Invoice::query()->where('customer_id', $customer->id)->outstanding()->count(),
                'current_balance' => $customer->balance,
            ],
            'usage' => [
                ['label' => 'Data used', 'value' => '284 GB', 'change' => '+12%'],
                ['label' => 'Avg. speed', 'value' => '92 Mbps', 'change' => 'stable'],
                ['label' => 'Uptime', 'value' => '99.9%', 'change' => '+0.1%'],
            ],
        ]);
    }
}
