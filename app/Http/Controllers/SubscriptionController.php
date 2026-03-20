<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\Customer;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
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
            ->through(fn ($subscription) => [
                'id' => $subscription->id,
                'subscription_code' => $subscription->subscription_code,
                'customer_name' => $subscription->customer->full_name ?? 'N/A',
                'customer_email' => $subscription->customer->email ?? 'N/A',
                'plan' => $subscription->plan?->name ?? 'N/A',
                'router' => $subscription->router?->name ?? 'N/A',
                'status' => $subscription->status,
                'total_price' => (float) $subscription->total_price,
                'billing_cycle' => $subscription->billing_cycle,
                'activation_date' => $subscription->activation_date?->format('M d, Y'),
                'created_at' => $subscription->created_at?->format('M d, Y'),
            ]);

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
        $customer = $customerId ? Customer::findOrFail($customerId) : null;
        $customers = Customer::get();
        $plans = Plan::active()->ordered()->get(['id', 'name', 'price', 'billing_cycle']);
        $routers = Router::where('status', 'online')->get(['id', 'name', 'site', 'vendor', 'model']);
        $ipPools = IpPool::active()->with('router')->get();

        return view('subscriptions.create', compact('customer', 'customers', 'plans', 'routers', 'ipPools'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();

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

            $item->calculateTotals();
            $totalPrice += $item->total;
        }

        // Update subscription total
        $subscription->update(['total_price' => $totalPrice]);

        return response()->json([
            'message' => 'Subscription created successfully.',
            'subscription' => $subscription->load('customer', 'plan', 'router'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Subscription $subscription): View
    {
        $subscription->load(['customer', 'plan', 'router', 'items']);

        return view('subscriptions.show', compact('subscription'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription): View
    {
        $subscription->load(['items']);
        $plans = Plan::active()->ordered()->get(['id', 'name', 'price', 'billing_cycle']);
        $routers = Router::where('status', 'online')->get(['id', 'name', 'site', 'vendor', 'model']);

        return view('subscriptions.edit', compact('subscription', 'plans', 'routers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'nullable|exists:plans,id',
            'router_id' => 'nullable|exists:routers,id',
            'site' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip|max:255',
            'pppoe_username' => 'nullable|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
            'billing_cycle' => 'nullable|in:monthly,quarterly,yearly',
            'status' => 'nullable|in:pending,active,suspended,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        // Handle activation
        if (isset($validated['status']) && $validated['status'] === 'active' && ! $subscription->activation_date) {
            $validated['activation_date'] = now();
        }

        $subscription->update($validated);

        return response()->json([
            'message' => 'Subscription updated successfully.',
            'subscription' => $subscription->fresh()->load('customer', 'plan', 'router'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription): JsonResponse
    {
        $subscription->delete();

        return response()->json([
            'message' => 'Subscription deleted successfully.',
        ]);
    }

    /**
     * Suspend a subscription.
     */
    public function suspend(Subscription $subscription): JsonResponse
    {
        $subscription->suspend();

        return response()->json([
            'message' => 'Subscription suspended successfully.',
        ]);
    }

    /**
     * Activate a subscription.
     */
    public function activate(Subscription $subscription): JsonResponse
    {
        $subscription->activate();

        return response()->json([
            'message' => 'Subscription activated successfully.',
        ]);
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Subscription $subscription): JsonResponse
    {
        $subscription->cancel();

        return response()->json([
            'message' => 'Subscription cancelled successfully.',
        ]);
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
}
