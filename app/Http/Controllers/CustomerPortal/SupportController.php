<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('customer.support.index', [
            'tickets' => collect(),
        ]);
    }
}
