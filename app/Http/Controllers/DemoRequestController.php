<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDemoRequestRequest;
use App\Models\DemoRequest;
use App\Services\TelegramLeadNotifier;
use Illuminate\Http\RedirectResponse;

class DemoRequestController extends Controller
{
    public function store(StoreDemoRequestRequest $request, TelegramLeadNotifier $telegramLeadNotifier): RedirectResponse
    {
        $validated = $request->validated();
        $sourcePage = $validated['source_page'] ?? 'pricing';

        $demoRequest = DemoRequest::query()->create([
            ...$validated,
            'source_page' => $sourcePage,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $telegramLeadNotifier->notifyDemoRequest($demoRequest);

        $redirect = $sourcePage === 'home'
            ? redirect()->to(route('home').'#guided-setup')
            : redirect()->route('pricing');

        return $redirect
            ->with('demo_request_success', 'Thanks. Your demo request has been received and our team will follow up soon.');
    }
}
