<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
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
