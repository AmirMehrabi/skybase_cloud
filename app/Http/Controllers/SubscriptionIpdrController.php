<?php

namespace App\Http\Controllers;

use App\Http\Requests\IpdrReportRequest;
use App\Models\Subscription;
use App\Services\IpdrClient;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class SubscriptionIpdrController extends Controller
{
    public function __invoke(IpdrReportRequest $request, Subscription $subscription, IpdrClient $client): View|JsonResponse
    {
        abort_unless(config('ipdr.enabled'), 404);
        abort_unless((string) $subscription->tenant_id === (string) tenant_id(), 403);
        $error = null;
        $report = null;
        try {
            $report = $client->report((string) $subscription->tenant_id, ['subscription_id' => (string) $subscription->id] + $request->validated());
        } catch (Throwable $exception) {
            report($exception);
            $error = 'IPDR is temporarily unavailable. Existing RADIUS quota accounting is unaffected.';
        }
        if ($request->expectsJson()) {
            return response()->json($report ?? ['message' => $error], $error ? 503 : 200);
        }

        return view('modules.subscriptions.ipdr', compact('subscription', 'report', 'error'));
    }
}
