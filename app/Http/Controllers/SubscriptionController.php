<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use App\Services\ActivityLogFormatter;
use App\Services\BillingService;
use App\Services\OrganizationBillingService;
use App\Services\RadiusAccountingUsageService;
use App\Services\RadiusProvisioningService;
use App\Services\SubscriptionSessionDisconnectService;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->paginate($request->input('per_page', 15))
            ->through(
                fn ($subscription) => [
                    'id' => $subscription->id,
                    'subscription_code' => $subscription->subscription_code,
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
        $ipPools = IpPool::active()->with('router')->get();

        return view('subscriptions.create', compact('customer', 'customers', 'plans', 'routers', 'ipPools'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $validated = $this->organizationBilling->applyDefaultsToSubscriptionAttributes($validated);
        $validated['tenant_id'] = auth()->user()->tenant_id ?? null;
        $validated['subscription_code'] = Subscription::generateSubscriptionCode();

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

        $subscription = Subscription::create($validated);

        // Create line items
        $totalPrice = 0;
        foreach ($validated['items'] as $itemData) {
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

        // Update subscription total
        $subscription->update(['total_price' => $totalPrice]);

        $invoice = $this->billing->createInvoiceForSubscription($subscription->fresh(['customer', 'plan', 'items']), includeOneTimeItems: true);

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
        $subscription->load(['customer.organization', 'plan', 'router', 'items', 'invoices.payments']);
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
        $subscription->load(['items', 'customer.organization.defaultPlan']);
        $plans = Plan::active()
            ->ordered()
            ->get(['id', 'name', 'price', 'billing_cycle']);
        $routers = Router::where('status', 'online')->get(['id', 'name', 'site', 'vendor', 'model']);

        return view('subscriptions.edit', compact('subscription', 'plans', 'routers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => 'nullable|exists:plans,id',
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
        ]);

        if (array_key_exists('plan_id', $validated)) {
            $this->organizationBilling->assertPlanAllowedForCustomer($subscription->customer_id, (int) $validated['plan_id']);
        }

        // Handle activation
        if (isset($validated['status']) && $validated['status'] === 'active' && ! $subscription->activation_date) {
            $validated['activation_date'] = now();
        }

        if (array_key_exists('billing_enabled', $validated)) {
            $validated['billing_disabled_at'] = $validated['billing_enabled'] ? null : ($subscription->billing_disabled_at ?? now());
        }

        $validated = $this->organizationBilling->applyDefaultsToSubscriptionAttributes([
            ...$subscription->only(['customer_id', 'plan_id', 'billing_cycle', 'billing_enabled']),
            ...$validated,
        ]);

        $subscription->update($validated);

        if ($subscription->customer?->organization?->billing_enabled) {
            $this->organizationBilling->applyDefaultsToExistingSubscription($subscription->fresh(['items']), $subscription->customer->organization);
        }

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        $subscription->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subscription deleted successfully.',
            ]);
        }

        return redirect()
            ->route('subscriptions.index')
            ->with('success', 'Subscription deleted successfully.');
    }

    /**
     * Suspend a subscription.
     */
    public function suspend(Request $request, Subscription $subscription, SubscriptionSessionDisconnectService $disconnectService): JsonResponse|RedirectResponse
    {
        $subscription->suspend();
        $subscription = $subscription->fresh(['router']);
        $disconnectResult = $disconnectService->disconnect($subscription);
        $disconnectService->recordActivity($subscription, $disconnectResult);

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
    public function activate(Request $request, Subscription $subscription, RadiusProvisioningService $radiusProvisioning): JsonResponse|RedirectResponse
    {
        $subscription->activate();
        $subscription = $subscription->fresh(['customer.organization', 'plan']);
        $radiusProvisioning->syncSubscription($subscription);
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
