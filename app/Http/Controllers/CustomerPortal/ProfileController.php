<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPortal\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('customer.profile.show', [
            'customer' => auth('customer')->user(),
        ]);
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $customer = $request->user('customer');
        $customer->update([
            'password' => $request->validated('password'),
        ]);

        $request->session()->regenerate();

        return redirect()
            ->route('customer.profile.show')
            ->with('success', 'Password changed successfully.');
    }
}
