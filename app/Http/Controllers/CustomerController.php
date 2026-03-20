<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('customers.index');
    }

    /**
     * Get paginated customers data for AJAX requests.
     */
    public function data(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'plan', 'site', 'router']);

        $customers = Customer::filter($filters)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15))
            ->through(fn ($customer) => [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'customer_code' => $customer->customer_code,
                'email' => $customer->email,
                'plan' => $customer->plan,
                'site' => $customer->site,
                'router' => $customer->router,
                'ip_address' => $customer->ip_address ?? 'N/A',
                'balance' => (float) $customer->balance,
                'status' => $customer->status,
                'created_at' => $customer->created_at?->format('M d, Y'),
            ]);

        return response()->json([
            'customers' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
        ]);
    }

    /**
     * Get filter options for the customers index page.
     */
    public function filterOptions(): JsonResponse
    {
        return response()->json(Customer::getFilterOptions());
    }

    /**
     * Get customer statistics.
     */
    public function stats(): JsonResponse
    {
        return response()->json(Customer::getStats());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $plans = Plan::active()->ordered()->get(['id', 'name', 'price', 'billing_cycle']);
        $routers = Router::where('status', 'online')->get(['id', 'name', 'site', 'vendor', 'model']);

        return view('customers.create', compact('plans', 'routers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Generate name from first/last name or company name
        if ($validated['customer_type'] === 'individual') {
            $validated['name'] = trim($validated['first_name'].' '.$validated['last_name']);
        } else {
            $validated['name'] = $validated['company_name'];
        }

        // Store plan and router names for backwards compatibility
        if (! empty($validated['plan_id'])) {
            $plan = Plan::find($validated['plan_id']);
            $validated['plan'] = $plan?->name;
        }

        if (! empty($validated['router_id'])) {
            $router = Router::find($validated['router_id']);
            $validated['router'] = $router?->name;
            $validated['site'] = $router?->site ?? $validated['site'] ?? null;
        }

        // Handle auto-activation
        if ($request->boolean('auto_activate') || $validated['status'] === 'active') {
            $validated['activation_date'] = now();
            $validated['status'] = 'active';
        }

        // Set tenant if not provided
        if (auth()->check() && empty($validated['tenant_id'])) {
            $validated['tenant_id'] = auth()->user()->tenant_id;
        }

        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => $customer,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer): View
    {
        $this->authorizeTenantAccess($customer);

        return view('customers.show', ['customer' => $customer]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer): View
    {
        $this->authorizeTenantAccess($customer);

        return view('customers.edit', ['customer' => $customer]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorizeTenantAccess($customer);

        $validated = $request->validated();

        // Update name based on customer type
        if ($validated['customer_type'] === 'individual') {
            $validated['name'] = trim($validated['first_name'].' '.$validated['last_name']);
        } else {
            $validated['name'] = $validated['company_name'];
        }

        // Handle activation date change
        if ($customer->status !== 'active' && $validated['status'] === 'active' && ! $customer->activation_date) {
            $validated['activation_date'] = now();
        }

        $customer->update($validated);

        return response()->json([
            'message' => 'Customer updated successfully.',
            'customer' => $customer->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorizeTenantAccess($customer);

        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully.',
        ]);
    }

    /**
     * Suspend a customer.
     */
    public function suspend(Customer $customer): JsonResponse
    {
        $this->authorizeTenantAccess($customer);

        $customer->update(['status' => 'suspended']);

        return response()->json([
            'message' => 'Customer suspended successfully.',
        ]);
    }

    /**
     * Activate a customer.
     */
    public function activate(Customer $customer): JsonResponse
    {
        $this->authorizeTenantAccess($customer);

        $customer->update([
            'status' => 'active',
            'activation_date' => $customer->activation_date ?? now(),
        ]);

        return response()->json([
            'message' => 'Customer activated successfully.',
        ]);
    }

    /**
     * Ensure the user has access to the customer's tenant.
     */
    protected function authorizeTenantAccess(Customer $customer): void
    {
        if (auth()->check() && auth()->user()->tenant_id && $customer->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'You do not have access to this customer.');
        }
    }
}
