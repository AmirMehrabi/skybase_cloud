<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
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
        ]);
    }
}
