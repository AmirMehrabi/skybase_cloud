<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Support\CustomerPortalSettings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(CustomerPortalSettings::showsBilling($request->user('customer')->tenant_id), 404);

        $invoices = $request->user('customer')
            ->invoices()
            ->with('subscription.plan')
            ->latest('issue_date')
            ->get();

        return view('customer.invoices.index', [
            'invoices' => $invoices,
        ]);
    }
}
