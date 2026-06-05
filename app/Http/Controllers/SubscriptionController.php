<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\BulkDeleteSubscriptionsRequest;
use App\Jobs\BulkDeleteModelsJob;
use App\Models\ActivityLog;
use App\Models\BulkDeletionRun;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionIpRoute;
use App\Models\SubscriptionItem;
use App\Services\ActivityLogFormatter;
use App\Services\BillingService;
use App\Services\Monitoring\RrdToolService;
use App\Services\Monitoring\SubscriptionBandwidthCollector;
use App\Services\OrganizationBillingService;
use App\Services\RadiusAccountingUsageService;
use App\Services\RadiusProvisioningService;
use App\Services\SubscriptionDeletionService;
use App\Services\SubscriptionIpRouteSyncService;
use App\Services\SubscriptionSessionDisconnectService;
use App\Services\TenantNotificationService;
use App\Support\Notifications\NotificationEventRegistry;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        protected BillingService $billing,
        protected OrganizationBillingService $organizationBilling,
        protected RadiusAccountingUsageService $radiusAccountingUsage,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('subscriptions.index');
    }

    /**
     * Get paginated subscriptions data for AJAX requests.
     */
    public function data(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'plan', 'customer']);

        $subscriptions = Subscription::filter($filters)
            ->with(['customer', 'plan', 'router'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 100))
            ->through(
                fn ($subscription) => [
                    'id' => $subscription->id,
                    'subscription_code' => $subscription->subscription_code,
                    'name' => $subscription->name,
                    'service_type' => $subscription->service_type,
                    'connection_type' => $subscription->connection_type,
                    'customer_name' => $subscription->customer->full_name ?? 'N/A',
                    'customer_email' => $subscription->customer->email ?? 'N/A',
                    'plan' => $subscription->plan?->name ?? 'N/A',
                    'router' => $subscription->router?->name ?? 'N/A',
                    'pppoe_username' => $subscription->pppoe_username,
                    'pppoe_password' => $subscription->pppoe_password,
                    'status' => $subscription->status,
                    'connection_status' => $subscription->connection_status,
                    'connection_status_checked_at' => $subscription->connection_status_checked_at?->format('M d, Y H:i'),
                    'total_price' => (float) $subscription->total_price,
                    'billing_cycle' => $subscription->billing_cycle,
                    'activation_date' => $subscription->activation_date?->format('M d, Y'),
                    'created_at' => $subscription->created_at?->format('M d, Y'),
                ],
            );

        return response()->json([
            'subscriptions' => $subscriptions->items(),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
                'from' => $subscriptions->firstItem(),
                'to' => $subscriptions->lastItem(),
            ],
        ]);
    }

    /**
     * Get subscription statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json(Subscription::getStats());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $customerId = $request->query('customer_id');
        $customer = $customerId ? Customer::with('organization.defaultPlan')->findOrFail($customerId) : null;
        $customers = Customer::with('organization.defaultPlan')->orderBy('name')->get();
        $plans = Plan::active()
            ->ordered()
            ->get(['id', 'name', 'price', 'billing_cycle']);
        $routers = Router::where('status', 'online')->get(['id', 'name', 'site', 'vendor', 'model']);
        $ipPools = IpPool::active()->with(['router', 'availableAddresses'])->get();

        return view('subscriptions.create', compact('customer', 'customers', 'plans', 'routers', 'ipPools'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $ipRoutes = $this->normalizedIpRouteRows($validated['ip_routes'] ?? []);
        unset($validated['ip_routes']);

        $validated = $this->organizationBilling->applyDefaultsToSubscriptionAttributes($validated);
        $validated['tenant_id'] = auth()->user()->tenant_id ?? null;
        $validated['subscription_code'] = Subscription::generateSubscriptionCode();
        $validated['name'] = $validated['name'] ?: Subscription::defaultNameForCustomer((int) $validated['customer_id']);
        $validated['service_type'] = $validated['service_type'] ?? 'hotspot';

        // Set base price from plan
        $plan = Plan::find($validated['plan_id']);
        $validated['base_price'] = $plan->price;

        // Handle activation
        if ($validated['status'] === 'active') {
            $validated['activation_date'] = now();
            $validated['start_date'] = $validated['start_date'] ?? now();
        }

        $validated['next_billing_date'] = $validated['start_date'] ?? now()->toDateString();
        $validated['billing_disabled_at'] = $validated['billing_enabled'] ? null : now();

        $items = $validated['items'];
        unset($validated['items']);

        $primaryIpAddress = null;
        if (($validated['ip_management'] ?? null) === 'system') {
            $primaryIpAddress = $validated['ip_address'] ?? null;
            unset($validated['ip_address']);
        }

        [$subscription, $invoice] = DB::transaction(function () use ($validated, $items, $primaryIpAddress, $ipRoutes): array {
            $subscription = Subscription::create($validated);

            if ($subscription->isSystemManagedIp()) {
                $assignedPrimaryIp = $subscription->assignIpAddress($primaryIpAddress);

                if (filled($primaryIpAddress) && ! $assignedPrimaryIp) {
                    throw ValidationException::withMessages([
                        'ip_address' => 'The selected primary IP address is not available in the current pool.',
                    ]);
                }

                if ($ipRoutes !== [] && blank($subscription->fresh()->ip_address)) {
                    throw ValidationException::withMessages([
                        'ip_address' => 'A primary IP address is required before IP routes can be configured.',
                    ]);
                }

                $this->replaceSubscriptionIpRoutes($subscription->fresh(['customer']), $ipRoutes);
            }

            $totalPrice = 0;
            foreach ($items as $itemData) {
                $item = new SubscriptionItem([
                    'subscription_id' => $subscription->id,
                    'item_type' => $itemData['item_type'],
                    'description' => $itemData['description'],
                    'plan_id' => $itemData['item_type'] === 'plan' ? $validated['plan_id'] : null,
                    'router_id' => $itemData['item_type'] === 'plan' ? $validated['router_id'] : null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                    'discount_type' => $itemData['discount_type'] ?? 'none',
                    'tax_percentage' => $itemData['tax_percentage'] ?? 0,
                    'recurring' => $itemData['recurring'],
                    'billing_cycle' => $itemData['billing_cycle'] ?? $validated['billing_cycle'],
                ]);

                if ($item->item_type === 'plan' && $subscription->customer?->organization?->billing_enabled) {
                    $this->organizationBilling->applyDefaultsToPlanItem($item, $subscription->customer->organization);
                    $totalPrice += $item->total;

                    continue;
                }

                $item->calculateTotals();
                $totalPrice += $item->total;
            }

            $subscription->update(['total_price' => $totalPrice]);
            $invoice = $this->billing->createInvoiceForSubscription($subscription->fresh(['customer', 'plan', 'items']), includeOneTimeItems: true);

            return [$subscription->fresh(['ipRoutes', 'router']), $invoice];
        });

        app(SubscriptionIpRouteSyncService::class)->syncRoutes($subscription);

        if ($request->expectsJson()) {
            return response()->json(
                [
                    'message' => $invoice
                        ? 'Subscription created successfully and invoice generated.'
                        : 'Subscription created successfully.',
                    'subscription' => $subscription->load('customer', 'plan', 'router'),
                    'invoice' => $invoice,
                ],
                201,
            );
        }

        return redirect()
            ->route('subscriptions.show', $subscription)
            ->with('success', $invoice
                ? 'Subscription created successfully and invoice generated.'
                : 'Subscription created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Subscription $subscription): View
    {
        $subscription->load(['customer.organization', 'plan', 'router', 'ipPool.router', 'ipRoutes.ipPool', 'items', 'invoices.payments']);
        $activityLog = app(ActivityLogFormatter::class)->forSubject($subscription, $subscription->tenant_id);
        $billingInvoices = $this->billingInvoicesForSubscription($subscription);
        $usageSummary = $this->usageSummaryForSubscription($subscription);
        $usageSessions = $this->usageSessionsForSubscription($subscription);

        return view('subscriptions.show', compact(
            'subscription',
            'activityLog',
            'billingInvoices',
            'usageSummary',
            'usageSessions',
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription): View
    {
        $subscription->load(['items', 'customer.organization.defaultPlan', 'ipPool.router', 'ipRoutes.ipPool']);
        $plans = Plan::active()
            ->ordered()
            ->get(['id', 'name', 'price', 'billing_cycle']);
        $routers = Router::where('status', 'online')->get(['id', 'name', 'site', 'vendor', 'model']);

        $ipPools = IpPool::active()->with(['router', 'availableAddresses'])->get();

        return view('subscriptions.edit', compact('subscription', 'plans', 'routers', 'ipPools'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => 'nullable|exists:plans,id',
            'name' => 'nullable|string|max:255',
            'service_type' => 'nullable|in:hotspot,pppoe,vpn',
            'router_id' => 'nullable|exists:routers,id',
            'site' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip|max:255',
            'pppoe_username' => 'nullable|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
            'billing_cycle' => 'nullable|in:monthly,quarterly,yearly',
            'billing_enabled' => 'nullable|boolean',
            'grace_period_days' => 'nullable|integer|min:0|max:365',
            'next_billing_date' => 'nullable|date',
            'status' => 'nullable|in:pending,active,suspended,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
            'sync_ip_routes' => 'nullable|boolean',
            'ip_routes' => 'nullable|array',
            'ip_routes.*' => 'array',
            'ip_routes.*.ip_pool_id' => 'nullable|integer|exists:ip_pools,id',
            'ip_routes.*.ip_address' => 'nullable|ip|max:255',
            'ip_routes.*.cidr' => 'nullable|integer|min:1|max:32',
        ]);
        $ipRoutesProvided = $request->boolean('sync_ip_routes') || array_key_exists('ip_routes', $validated);
        $ipRoutes = $this->normalizedIpRouteRows($validated['ip_routes'] ?? []);
        unset($validated['sync_ip_routes'], $validated['ip_routes']);
        $ipAddress = array_key_exists('ip_address', $validated) ? $validated['ip_address'] : null;
        $ipAddressProvided = array_key_exists('ip_address', $validated);
        unset($validated['ip_address']);

        DB::transaction(function () use ($subscription, $validated, $ipAddress, $ipAddressProvided, $ipRoutesProvided, $ipRoutes): void {
            if (array_key_exists('plan_id', $validated)) {
                $this->organizationBilling->assertPlanAllowedForCustomer($subscription->customer_id, (int) $validated['plan_id']);
            }

            if (isset($validated['status']) && $validated['status'] === 'active' && ! $subscription->activation_date) {
                $validated['activation_date'] = now();
            }

            if (array_key_exists('billing_enabled', $validated)) {
                $validated['billing_disabled_at'] = $validated['billing_enabled'] ? null : ($subscription->billing_disabled_at ?? now());
            }

            if (array_key_exists('name', $validated) && blank($validated['name'])) {
                $validated['name'] = Subscription::defaultNameForCustomer((int) $subscription->customer_id);
            }

            $validated = $this->organizationBilling->applyDefaultsToSubscriptionAttributes([
                ...$subscription->only(['customer_id', 'plan_id', 'billing_cycle', 'billing_enabled']),
                ...$validated,
            ]);

            $subscription->update($validated);

            if ($ipAddressProvided) {
                $updatedIp = $subscription->updateIpAddress($ipAddress);

                if ($ipAddress !== null && blank($ipAddress) === false && ! $updatedIp && $subscription->ip_address !== $ipAddress) {
                    throw ValidationException::withMessages([
                        'ip_address' => 'The selected IP address is not available in the current pool.',
                    ]);
                }
            }

            if ($ipRoutesProvided) {
                $subscription->refresh();

                if (! $subscription->isSystemManagedIp()) {
                    throw ValidationException::withMessages([
                        'ip_routes' => 'IP routes are only available when IP Management is set to System Managed.',
                    ]);
                }

                if ($ipRoutes !== [] && blank($subscription->ip_address)) {
                    throw ValidationException::withMessages([
                        'ip_address' => 'A primary IP address is required before IP routes can be configured.',
                    ]);
                }

                $this->replaceSubscriptionIpRoutes($subscription->fresh(['customer', 'ipRoutes']), $ipRoutes);
            }

            if ($subscription->customer?->organization?->billing_enabled) {
                $this->organizationBilling->applyDefaultsToExistingSubscription($subscription->fresh(['items']), $subscription->customer->organization);
            }
        });

        app(SubscriptionIpRouteSyncService::class)->syncRoutes($subscription->fresh(['router', 'ipRoutes']));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subscription updated successfully.',
                'subscription' => $subscription->fresh()->load('customer', 'plan', 'router'),
            ]);
        }

        return redirect()
            ->route('subscriptions.show', $subscription)
            ->with('success', 'Subscription updated successfully.');
    }

    public function suggestIp(Subscription $subscription): JsonResponse
    {
        $subscription->loadMissing('ipPool');

        if (! $subscription->isSystemManagedIp() || ! $subscription->ipPool) {
            return response()->json([
                'message' => 'This subscription does not use a system-managed IP pool.',
            ], 422);
        }

        $suggestedIp = $subscription->suggestIpAddress();

        if (! $suggestedIp) {
            return response()->json([
                'message' => 'No free IP address is available in the current pool.',
            ], 422);
        }

        return response()->json([
            'ip_address' => $suggestedIp->ip_address,
            'pool_name' => $subscription->ipPool->name,
            'available_ips' => $subscription->ipPool->available_ips,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        app(SubscriptionDeletionService::class)->delete($subscription, suppressActivityLogs: true);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subscription deleted successfully.',
            ]);
        }

        return redirect()
            ->route('subscriptions.index')
            ->with('success', 'Subscription deleted successfully.');
    }

    public function bulkDestroy(BulkDeleteSubscriptionsRequest $request): JsonResponse|RedirectResponse
    {
        $tenantId = (string) $request->user()->tenant_id;
        $validated = $request->validated();
        $filters = array_filter(
            $request->only(['search', 'status', 'plan', 'customer']),
            fn (mixed $value): bool => filled($value),
        );

        $run = BulkDeletionRun::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'module' => BulkDeletionRun::MODULE_SUBSCRIPTIONS,
            'action' => BulkDeletionRun::ACTION_DELETE,
            'selection_mode' => $validated['selection_mode'],
            'filters' => $filters,
            'selected_ids' => $validated['ids'] ?? [],
            'excluded_ids' => $validated['excluded_ids'] ?? [],
            'status' => BulkDeletionRun::STATUS_QUEUED,
        ]);

        ActivityLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'action' => 'subscription.bulk_delete_queued',
            'model_type' => BulkDeletionRun::class,
            'model_id' => $run->id,
            'new_values' => [
                'selection_mode' => $validated['selection_mode'],
                'filters' => $filters,
                'selected_ids_count' => count($validated['ids'] ?? []),
                'excluded_ids_count' => count($validated['excluded_ids'] ?? []),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        BulkDeleteModelsJob::dispatch($run->id);

        $message = 'Subscription bulk delete queued. The cleanup will run in the background.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'run_id' => $run->id,
            ], 202);
        }

        return back()->with('success', $message);
    }

    /**
     * Suspend a subscription.
     */
    public function suspend(Request $request, Subscription $subscription, SubscriptionSessionDisconnectService $disconnectService, TenantNotificationService $notifications): JsonResponse|RedirectResponse
    {
        $subscription->suspend();
        $subscription = $subscription->fresh(['customer', 'plan', 'router']);
        $disconnectResult = $disconnectService->disconnect($subscription);
        $disconnectService->recordActivity($subscription, $disconnectResult);
        $notifications->notifyAdmins($subscription->tenant_id, NotificationEventRegistry::SUBSCRIPTION_SUSPENDED, [
            'title' => 'Subscription suspended',
            'body' => "{$subscription->subscription_code} was suspended.",
            'action_url' => route('subscriptions.show', $subscription),
        ], $subscription);

        if ($subscription->customer) {
            $notifications->notifyCustomer($subscription->customer, NotificationEventRegistry::SUBSCRIPTION_SUSPENDED, [
                'title' => 'Your subscription was suspended',
                'body' => "{$subscription->subscription_code} is currently suspended.",
                'category' => 'service',
                'action_url' => route('customer.subscriptions.index'),
            ], $subscription);
        }

        if ($disconnectResult->shouldAlert()) {
            $notifications->notifyAdmins($subscription->tenant_id, NotificationEventRegistry::OPERATIONAL_FAILURE, [
                'title' => 'Router disconnect failed',
                'body' => $disconnectResult->message,
                'action_url' => route('subscriptions.show', $subscription),
            ], $subscription);
        }

        $message = 'Subscription suspended successfully.';

        if ($disconnectResult->shouldAlert()) {
            $message .= ' Router session disconnect warning: '.$disconnectResult->message;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'disconnect' => $disconnectResult->context(),
            ]);
        }

        return redirect()
            ->route('subscriptions.show', $subscription)
            ->with($disconnectResult->shouldAlert() ? 'warning' : 'success', $message);
    }

    /**
     * Activate a subscription.
     */
    public function activate(Request $request, Subscription $subscription, RadiusProvisioningService $radiusProvisioning, TenantNotificationService $notifications): JsonResponse|RedirectResponse
    {
        $subscription->activate();
        $subscription = $subscription->fresh(['customer.organization', 'plan']);
        $radiusProvisioning->syncSubscription($subscription);
        $notifications->notifyAdmins($subscription->tenant_id, NotificationEventRegistry::SUBSCRIPTION_ACTIVATED, [
            'title' => 'Subscription activated',
            'body' => "{$subscription->subscription_code} was activated.",
            'action_url' => route('subscriptions.show', $subscription),
        ], $subscription);

        if ($subscription->customer) {
            $notifications->notifyCustomer($subscription->customer, NotificationEventRegistry::SUBSCRIPTION_ACTIVATED, [
                'title' => 'Your subscription is active',
                'body' => "{$subscription->subscription_code} is now active.",
                'category' => 'service',
                'action_url' => route('customer.subscriptions.index'),
            ], $subscription);
        }
        $radiusSkipReason = $radiusProvisioning->provisioningSkipReason($subscription);
        $radiusWarning = $radiusSkipReason !== null
            ? ' Radius entries were not created: '.$radiusSkipReason.'.'
            : '';
        $radiusWarning = $radiusWarning === '' && $radiusProvisioning->rateLimitForPlan($subscription->plan) === null
            ? ' Radius credentials were created, but no Mikrotik-Rate-Limit was written because the plan has no upload/download speed.'
            : $radiusWarning;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subscription activated successfully.'.$radiusWarning,
            ]);
        }

        return redirect()
            ->route('subscriptions.show', $subscription)
            ->with('success', 'Subscription activated successfully.'.$radiusWarning);
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Request $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        $subscription->cancel();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subscription cancelled successfully.',
            ]);
        }

        return redirect()
            ->route('subscriptions.show', $subscription)
            ->with('success', 'Subscription cancelled successfully.');
    }

    public function updateBilling(Request $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'billing_enabled' => 'required|boolean',
            'grace_period_days' => 'nullable|integer|min:0|max:365',
            'next_billing_date' => 'nullable|date',
        ]);

        $subscription->update([
            'billing_enabled' => $validated['billing_enabled'],
            'billing_disabled_at' => $validated['billing_enabled'] ? null : ($subscription->billing_disabled_at ?? now()),
            'grace_period_days' => $validated['grace_period_days'] ?? null,
            'next_billing_date' => $validated['next_billing_date'] ?? $subscription->next_billing_date,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subscription billing settings updated successfully.',
                'subscription' => $subscription->fresh(),
            ]);
        }

        return back()->with('success', 'Subscription billing settings updated successfully.');
    }

    public function generateInvoice(Subscription $subscription): JsonResponse|RedirectResponse
    {
        $invoice = $this->billing->createInvoiceForSubscription($subscription->load(['customer', 'plan', 'items']), includeOneTimeItems: true);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => $invoice ? 'Invoice generated successfully.' : 'Billing is disabled or no billable items were found.',
                'invoice' => $invoice,
            ], $invoice ? 201 : 422);
        }

        return back()->with(
            $invoice ? 'success' : 'error',
            $invoice ? 'Invoice generated successfully.' : 'Billing is disabled or no billable items were found.',
        );
    }

    /**
     * Check if a PPPoE username is already taken.
     */
    public function checkPppoeUsername(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|max:255',
        ]);

        $username = $request->query('username');
        $tenantId = auth()->user()?->tenant_id;
        $subscriptionId = $request->query('exclude_subscription_id');

        // Check if username exists in current tenant's subscriptions
        $query = Subscription::where('pppoe_username', $username)
            ->where('status', '!=', 'cancelled')
            ->when($tenantId, function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId);
            })
            ->when($subscriptionId, function ($query) use ($subscriptionId) {
                return $query->where('id', '!=', $subscriptionId);
            });

        $existingSubscription = $query->first();

        if (! $existingSubscription) {
            return response()->json([
                'available' => true,
                'username' => $username,
                'message' => 'Username is available',
            ]);
        }

        return response()->json([
            'available' => false,
            'username' => $username,
            'subscription_code' => $existingSubscription->subscription_code,
            'customer' => $existingSubscription->customer->full_name ?? null,
            'message' => 'Username is already taken by '.($existingSubscription->customer->full_name ?? 'another customer'),
        ]);
    }

    public function liveBandwidth(Subscription $subscription, SubscriptionBandwidthCollector $collector): JsonResponse
    {
        $this->authorizeTenantAccess($subscription);
        $state = $subscription->bandwidthState;
        $stale = ! $state?->sampled_at || $state->sampled_at->lte(now()->subSeconds((int) config('monitoring.subscription_live_ttl_seconds')));

        if ($stale) {
            $collector->collect($subscription->fresh(['router']));
            $state = $subscription->fresh('bandwidthState')->bandwidthState;
        }

        return response()->json([
            'rx_bps' => (int) ($state?->rx_bps ?? 0),
            'tx_bps' => (int) ($state?->tx_bps ?? 0),
            'interface_name' => $state?->interface_name,
            'source' => $state?->source ?? 'routeros',
            'sampled_at' => $state?->sampled_at?->diffForHumans(),
            'error' => $state?->error,
        ]);
    }

    public function bandwidthHistory(Request $request, Subscription $subscription, RrdToolService $rrdTool): JsonResponse
    {
        $this->authorizeTenantAccess($subscription);

        try {
            $chartData = collect($rrdTool->subscriptionBandwidthSeries($subscription, (string) $request->query('range', '1h')))
                ->map(fn (array $row): array => [
                    ...$row,
                    'time' => date('H:i', (int) $row['timestamp']),
                ])
                ->values();
        } catch (\Throwable) {
            $chartData = collect();
        }

        return response()->json([
            'chartData' => $chartData,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function billingInvoicesForSubscription(Subscription $subscription): array
    {
        return $subscription->invoices
            ->sortByDesc(fn (Invoice $invoice): int => optional($invoice->issue_date ?? $invoice->created_at)->timestamp ?? 0)
            ->values()
            ->map(function (Invoice $invoice): array {
                $latestPayment = $invoice->payments
                    ->sortByDesc(fn ($payment): int => optional($payment->paid_at ?? $payment->created_at)->timestamp ?? 0)
                    ->first();

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => (float) $invoice->total,
                    'balance_due' => (float) $invoice->balance_due,
                    'due_date' => $invoice->due_date?->toDateString(),
                    'status' => $invoice->status,
                    'paid_date' => ($latestPayment?->paid_at ?? $latestPayment?->created_at)?->toDateString(),
                    'url' => route('billing.invoices.show', $invoice),
                ];
            })
            ->all();
    }

    private function authorizeTenantAccess(Subscription $subscription): void
    {
        if (auth()->check() && auth()->user()->tenant_id && $subscription->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'You do not have access to this subscription.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $routes
     * @return array<int, array{ip_pool_id: int, ip_address: string, cidr: int}>
     */
    private function normalizedIpRouteRows(array $routes): array
    {
        return collect($routes)
            ->filter(fn (mixed $route): bool => is_array($route) && (filled($route['ip_pool_id'] ?? null) || filled($route['ip_address'] ?? null)))
            ->map(fn (array $route): array => [
                'ip_pool_id' => (int) ($route['ip_pool_id'] ?? 0),
                'ip_address' => (string) ($route['ip_address'] ?? ''),
                'cidr' => (int) ($route['cidr'] ?? 32),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{ip_pool_id: int, ip_address: string, cidr: int}>  $routeRows
     */
    private function replaceSubscriptionIpRoutes(Subscription $subscription, array $routeRows): void
    {
        $subscription->loadMissing(['customer', 'ipRoutes']);

        $destinations = [];
        foreach ($routeRows as $index => $routeRow) {
            $destination = $routeRow['ip_address'].'/'.$routeRow['cidr'];
            if (in_array($destination, $destinations, true)) {
                throw ValidationException::withMessages([
                    "ip_routes.{$index}.ip_address" => 'Duplicate IP route destinations are not allowed.',
                ]);
            }
            $destinations[] = $destination;
        }

        $existingRoutes = $subscription->ipRoutes->keyBy(fn (SubscriptionIpRoute $route): string => $route->destinationAddress());
        $submittedDestinations = collect($routeRows)
            ->map(fn (array $routeRow): string => $routeRow['ip_address'].'/'.$routeRow['cidr'])
            ->all();

        foreach ($subscription->ipRoutes as $existingRoute) {
            if (in_array($existingRoute->destinationAddress(), $submittedDestinations, true)) {
                continue;
            }

            app(SubscriptionIpRouteSyncService::class)->removeRoute($existingRoute, $subscription);
            $this->releaseRouteIpAddress($existingRoute);
            $existingRoute->delete();
        }

        foreach ($routeRows as $index => $routeRow) {
            $destination = $routeRow['ip_address'].'/'.$routeRow['cidr'];
            $route = $existingRoutes->get($destination);
            $ipAddress = $this->availableRouteIpAddress($subscription, $routeRow, $route, $index);

            if (! $route) {
                $route = SubscriptionIpRoute::create([
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'ip_pool_id' => $routeRow['ip_pool_id'],
                    'ip_address' => $routeRow['ip_address'],
                    'cidr' => $routeRow['cidr'],
                ]);

                $route->forceFill([
                    'routeros_comment' => $route->routerOsComment(),
                ])->save();
            }

            if ($route->ip_address_id && (int) $route->ip_address_id !== (int) $ipAddress->id) {
                $this->releaseRouteIpAddress($route);
            }

            $route->forceFill([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'ip_pool_id' => $routeRow['ip_pool_id'],
                'ip_address_id' => $ipAddress->id,
                'ip_address' => $routeRow['ip_address'],
                'cidr' => $routeRow['cidr'],
                'routeros_sync_status' => 'pending',
                'routeros_sync_error' => null,
            ])->save();

            $this->assignRouteIpAddress($ipAddress, $subscription, $route);
        }
    }

    /**
     * @param  array{ip_pool_id: int, ip_address: string, cidr: int}  $routeRow
     */
    private function availableRouteIpAddress(Subscription $subscription, array $routeRow, ?SubscriptionIpRoute $route, int $index): IpAddress
    {
        $ipAddress = IpAddress::query()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('ip_pool_id', $routeRow['ip_pool_id'])
            ->where('ip_address', $routeRow['ip_address'])
            ->first();

        if (! $ipAddress) {
            throw ValidationException::withMessages([
                "ip_routes.{$index}.ip_address" => 'The selected IP route address does not belong to the selected IPAM pool.',
            ]);
        }

        if (! $ipAddress->isAvailable() && (! $route || (int) $route->ip_address_id !== (int) $ipAddress->id)) {
            throw ValidationException::withMessages([
                "ip_routes.{$index}.ip_address" => 'The selected IP route address is not available.',
            ]);
        }

        return $ipAddress;
    }

    private function assignRouteIpAddress(IpAddress $ipAddress, Subscription $subscription, SubscriptionIpRoute $route): void
    {
        $ipAddress->forceFill([
            'status' => 'assigned',
            'customer_id' => $subscription->customer_id,
            'mac_address' => null,
            'subscription_code' => null,
            'assigned_at' => now(),
            'released_at' => null,
            'notes' => 'Subscription IP route '.$subscription->subscription_code,
            'metadata' => [
                'purpose' => 'subscription_ip_route',
                'subscription_id' => $subscription->id,
                'subscription_ip_route_id' => $route->id,
            ],
        ])->save();
    }

    private function releaseSubscriptionIpRoutes(Subscription $subscription): void
    {
        $subscription->loadMissing('ipRoutes');

        foreach ($subscription->ipRoutes as $route) {
            $this->releaseRouteIpAddress($route);
        }
    }

    private function releaseRouteIpAddress(SubscriptionIpRoute $route): void
    {
        if (! $route->ip_address_id) {
            return;
        }

        $ipAddress = IpAddress::query()->find($route->ip_address_id);

        if (! $ipAddress) {
            return;
        }

        $ipAddress->release();
        $ipAddress->forceFill([
            'notes' => null,
            'metadata' => null,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function usageSummaryForSubscription(Subscription $subscription): array
    {
        $sessions = $this->radiusAccountingUsage->sessionsForSubscription(
            $subscription,
            $this->usageWindowStartForSubscription($subscription),
            now(),
            500,
        );
        $downloadBytes = (int) $sessions->sum('download');
        $uploadBytes = (int) $sessions->sum('upload');
        $totalBytes = $downloadBytes + $uploadBytes;
        $quotaBytes = $this->quotaBytesForPlan($subscription->plan);
        $peakSession = $sessions->sortByDesc('total')->first();
        $latestSession = $sessions->sortByDesc('last_activity_sort')->first();

        return [
            'window' => $this->usageWindowLabelForSubscription($subscription),
            'download_gb' => round($downloadBytes / 1073741824, 2),
            'upload_gb' => round($uploadBytes / 1073741824, 2),
            'total_gb' => round($totalBytes / 1073741824, 2),
            'quota_gb' => $quotaBytes > 0 ? round($quotaBytes / 1073741824, 2) : 0,
            'quota_label' => $quotaBytes > 0 ? number_format($quotaBytes / 1073741824, 2).' GB' : 'Unlimited',
            'usage_percent' => $quotaBytes > 0 ? round(min(($totalBytes / $quotaBytes) * 100, 100), 1) : 0,
            'sessions' => $sessions->count(),
            'peak_gb' => round((int) ($peakSession['total'] ?? 0) / 1073741824, 2),
            'peak_time' => $peakSession['last_activity_date_label'] ?? 'No usage yet',
            'last_activity' => $latestSession['last_activity'] ?? 'No usage yet',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function usageSessionsForSubscription(Subscription $subscription): array
    {
        return $this->radiusAccountingUsage->sessionsForSubscription(
            $subscription,
            $this->usageWindowStartForSubscription($subscription),
            now(),
            25,
        )
            ->map(fn (array $session): array => [
                'date' => $session['started_at_label'],
                'stopped_at' => $session['stopped_at_label'],
                'duration' => $session['duration'],
                'download' => $session['download_label'],
                'upload' => $session['upload_label'],
                'total' => $session['total_label'],
                'router' => $session['router'],
                'ip_address' => $session['ip_address'],
                'status' => $session['status'],
                'terminate_cause' => $session['terminate_cause'],
            ])
            ->values()
            ->all();
    }

    private function usageWindowStartForSubscription(Subscription $subscription): CarbonInterface
    {
        return $subscription->last_billed_at?->copy()->startOfDay()
            ?? $subscription->start_date?->copy()->startOfDay()
            ?? now()->subDays(30)->startOfDay();
    }

    private function usageWindowLabelForSubscription(Subscription $subscription): string
    {
        $start = $subscription->last_billed_at?->copy()->startOfDay()
            ?? $subscription->start_date?->copy()->startOfDay()
            ?? now()->subDays(30)->startOfDay();

        return $start->format('M d, Y').' - '.now()->format('M d, Y');
    }

    private function quotaBytesForPlan(?Plan $plan): int
    {
        if (! $plan || $plan->unlimited || ! $plan->data_limit) {
            return 0;
        }

        return match ($plan->data_unit) {
            'MB' => (int) round((float) $plan->data_limit * 1048576),
            'TB' => (int) round((float) $plan->data_limit * 1099511627776),
            default => (int) round((float) $plan->data_limit * 1073741824),
        };
    }
}
