<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return view('profile.index', compact('user'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $user->update($request->validated());

        return redirect()->route('profile.index')->with('success', 'Profile updated successfully.');
    }

    public function changePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $user = auth()->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->with('error', 'The current password is incorrect.');
        }

        $user->update([
            'password' => $request->input('password'),
        ]);

        return redirect()->route('profile.index')->with('success', 'Password changed successfully.');
    }
}
