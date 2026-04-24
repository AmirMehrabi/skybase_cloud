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
        $demoRequest = DemoRequest::query()->create([
            ...$request->validated(),
            'source_page' => 'pricing',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $telegramLeadNotifier->notifyDemoRequest($demoRequest);

        return redirect()
            ->route('pricing')
            ->with('demo_request_success', 'Thanks. Your demo request has been received and our team will follow up soon.');
    }
}
