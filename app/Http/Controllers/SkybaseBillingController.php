<?php

namespace App\Http\Controllers;

use App\Http\Requests\Billing\CheckoutRequest;
use App\Http\Requests\Billing\SettleDummyPaymentRequest;
use App\Models\SkybaseAccount;
use App\Models\SkybaseInvoice;
use App\Models\SkybasePaymentAttempt;
use App\Models\Tenant;
use App\Services\SkybaseBillingService;
use App\Services\SkybasePackageCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SkybaseBillingController extends Controller
{
    public function index(SkybasePackageCatalog $catalog): View
    {
        $tenantId = tenant()->id;

        return view('modules.billing.index', ['account' => SkybaseAccount::query()->where('tenant_id', $tenantId)->first(), 'invoices' => SkybaseInvoice::query()->where('tenant_id', $tenantId)->latest()->paginate(20), 'packages' => $catalog->all()]);
    }

    public function checkout(CheckoutRequest $request, SkybaseBillingService $billing): RedirectResponse
    {
        $attempt = $billing->checkout(tenant()->id, $request->validated('package_key'));

        return $attempt ? redirect()->route('skybase.billing.payment', $attempt->authority) : redirect()->route('skybase.billing.index')->with('success', 'Free package activated.');
    }

    public function payment(string $authority): View
    {
        $attempt = SkybasePaymentAttempt::query()->where('tenant_id', tenant()->id)->where('authority', $authority)->firstOrFail();
        $invoice = SkybaseInvoice::query()->where('tenant_id', tenant()->id)->findOrFail($attempt->skybase_invoice_id);

        return view('modules.billing.payment', compact('attempt', 'invoice'));
    }

    public function settle(SettleDummyPaymentRequest $request, string $authority, SkybaseBillingService $billing): RedirectResponse
    {
        $attempt = $billing->settle(tenant()->id, $authority, $request->validated('outcome'));

        return redirect()->route('skybase.billing.payment', $attempt->authority)->with('success', 'Simulation result: '.$attempt->status.'. No money was collected.');
    }

    public function cancel(SkybaseBillingService $billing): RedirectResponse
    {
        (new Tenant)->getConnection()->transaction(function () use ($billing): void {
            Tenant::query()->whereKey(tenant()->id)->lockForUpdate()->firstOrFail();
            $billing->account(tenant()->id)->update(['cancel_at_period_end' => true]);
            SkybaseInvoice::query()->where('tenant_id', tenant()->id)->where('status', 'pending')->update(['status' => 'void']);
            $billing->event(tenant()->id, 'billing.cancel_requested', 'Owner cancelled renewal; existing term preserved');
        });

        return back()->with('success', 'Renewal cancelled. Existing access is unchanged through the current term.');
    }
}
