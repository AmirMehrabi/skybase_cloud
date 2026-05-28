<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class PagesController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }

        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if (! config('app.cloud.enabled')) {
            return redirect()->route($this->guestEntryRouteName());
        }

        return view('home');
    }

    public function pricing(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('pricing');
    }

    public function features(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('features');
    }

    public function changelog(): View
    {
        return view('changelog');
    }

    public function governmentBrochure(): View
    {
        return view('brochures.government-fa');
    }

    public function contact(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('contact');
    }

    private function guestEntryRouteName(): string
    {
        return config('app.cloud.guest_entry') === 'customer' && Route::has('customer.login')
            ? 'customer.login'
            : 'auth.login';
    }
}
