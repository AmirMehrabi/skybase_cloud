<?php

namespace App\Http\Controllers;

use App\Http\Requests\RadiusAllocationRequest;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionIpLease;
use App\Services\SubscriptionIpAllocationService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class RadiusAllocationController extends Controller
{
    public function allocate(RadiusAllocationRequest $request, string $tenantId, SubscriptionIpAllocationService $allocator): JsonResponse
    {
        return app(TenantContext::class)->run($tenantId, function () use ($request, $allocator, $tenantId): JsonResponse {
            abort_unless(tenant()->status === 'active', 403);
            $subscription = Subscription::query()->where('tenant_id', $tenantId)
                ->where('router_id', $request->integer('router_id'))->where('pppoe_username', $request->string('username')->toString())->firstOrFail();
            $lease = $allocator->allocate($subscription, $request->integer('router_id'), $request->string('session_key')->toString());

            return response()->json([
                'reply:Framed-IP-Address' => ['op' => ':=', 'value' => $lease->ip_address],
                'reply:Port-Limit' => ['op' => ':=', 'value' => $subscription->concurrent_devices],
                'reply:Acct-Interim-Interval' => ['op' => ':=', 'value' => 60],
                'reply:Class' => ['op' => '+=', 'value' => 'skybase-lease:'.$lease->token],
            ]);
        });
    }

    public function reset(RadiusAllocationRequest $request, string $tenantId, SubscriptionIpAllocationService $allocator): JsonResponse
    {
        return app(TenantContext::class)->run($tenantId, function () use ($request, $allocator, $tenantId): JsonResponse {
            $router = Router::query()->where('tenant_id', $tenantId)->findOrFail($request->integer('router_id'));
            $resetAt = Carbon::parse($request->input('reset_at'));
            $leases = SubscriptionIpLease::query()->where('tenant_id', $tenantId)->where('router_id', $router->id)
                ->whereNull('released_at')->where('last_seen_at', '<=', $resetAt)->get();
            foreach ($leases as $lease) {
                $subscription = Subscription::query()->withTrashed()->where('tenant_id', $tenantId)->findOrFail($lease->subscription_id);
                $allocator->release($subscription, $lease->token, seenBefore: $resetAt);
            }

            return response()->json(['status' => 'ok']);
        });
    }

    public function accounting(RadiusAllocationRequest $request, string $tenantId, SubscriptionIpAllocationService $allocator): JsonResponse
    {
        return app(TenantContext::class)->run($tenantId, function () use ($request, $allocator, $tenantId): JsonResponse {
            $subscription = Subscription::query()->withTrashed()->where('tenant_id', $tenantId)
                ->where('router_id', $request->integer('router_id'))->where('pppoe_username', $request->string('username')->toString())->firstOrFail();
            $token = $request->string('lease_token')->toString();
            if ($request->input('event') === 'stop') {
                $allocator->release($subscription, $token);
            } else {
                $allocator->confirm($subscription, $token);
            }

            return response()->json(['status' => 'ok']);
        });
    }
}
