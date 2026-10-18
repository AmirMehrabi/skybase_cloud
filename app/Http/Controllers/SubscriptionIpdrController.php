<?php

namespace App\Http\Controllers;

use App\Http\Requests\IpdrReportRequest;
use App\Models\Subscription;
use App\Services\IpdrClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Throwable;

class SubscriptionIpdrController extends Controller
{
    public function __invoke(IpdrReportRequest $request, Subscription $subscription, IpdrClient $client): RedirectResponse|JsonResponse
    {
        abort_unless(config('ipdr.enabled'), 404);
        abort_unless((string) $subscription->tenant_id === (string) tenant_id(), 403);
        if (! $request->expectsJson()) {
            return redirect()->route('subscriptions.show', ['subscription' => $subscription, 'tab' => 'ipdr'] + $request->validated());
        }
        $error = null;
        $report = null;
        try {
            $report = $client->report((string) $subscription->tenant_id, ['subscription_id' => (string) $subscription->id] + $request->validated());
        } catch (Throwable $exception) {
            report($exception);
            $error = 'IPDR is temporarily unavailable. Existing RADIUS quota accounting is unaffected.';
        }

        return response()->json($report ?? ['message' => $error], $error ? 503 : 200);
    }
}
