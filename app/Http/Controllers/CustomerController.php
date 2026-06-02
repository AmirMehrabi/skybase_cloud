<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerNoteRequest;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Requests\NotificationPreferenceRequest;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Organization;
use App\Services\ActivityLogFormatter;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        $filters = $request->only(['search', 'status', 'plan', 'site', 'router', 'organization']);

        $customers = Customer::filter($filters)
            ->with('organization')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15))
            ->through(fn ($customer) => [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'customer_code' => $customer->customer_code,
                'email' => $customer->email,
                'organization' => $customer->organization?->name ?? 'Unassigned',
                'organization_id' => $customer->organization_id,
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
        return view('customers.create', [
            'organizations' => Organization::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        // Generate name from first/last name or company name
        if ($validated['customer_type'] === 'individual') {
            $validated['name'] = trim($validated['first_name'].' '.$validated['last_name']);
            $validated['company_name'] = null;
        } else {
            $validated['name'] = $validated['company_name'];
            $validated['first_name'] = null;
            $validated['last_name'] = null;
            $validated['national_id'] = null;
        }

        // Set tenant if not provided
        if (auth()->check() && empty($validated['tenant_id'])) {
            $validated['tenant_id'] = auth()->user()->tenant_id;
        }

        $validated['billing_disabled_at'] = $validated['billing_enabled'] ? null : now();

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $customer = Customer::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Customer created successfully.',
                'redirect_to' => route('subscriptions.create', ['customer_id' => $customer->id]),
                'customer' => $customer,
            ], 201);
        }

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer): View
    {
        $this->authorizeTenantAccess($customer);

        $customer->load([
            'payments.invoice',
            'invoices.payments',
            'invoices.subscription',
            'subscriptions.plan',
            'subscriptions.router',
            'tickets.team',
            'tickets.assignedUser',
            'tickets.subscription',
            'notes.author',
            'organization',
        ]);

        $activitySubjects = collect([$customer])
            ->merge($customer->subscriptions)
            ->merge($customer->invoices)
            ->merge($customer->payments);

        $activities = Activity::query()
            ->forTenant($customer->tenant_id)
            ->where(function ($query) use ($activitySubjects): void {
                foreach ($activitySubjects as $subject) {
                    $query->orWhere(function ($query) use ($subject): void {
                        $query->where('subject_type', $subject->getMorphClass())
                            ->where('subject_id', $subject->getKey());
                    });
                }
            })
            ->with('causer')
            ->latest()
            ->limit(30)
            ->get();

        $activityLog = app(ActivityLogFormatter::class)->formatCollection($activities);

        return view('customers.show', [
            'customer' => $customer,
            'activityLog' => $activityLog,
            'notificationPreference' => app(NotificationPreferenceService::class)->settingsFor($customer),
            'unreadNotificationsCount' => $customer->notifications()
                ->where('tenant_id', $customer->tenant_id)
                ->whereNull('read_at')
                ->whereNull('archived_at')
                ->count(),
        ]);
    }

    public function storeNote(StoreCustomerNoteRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorizeTenantAccess($customer);

        $customer->notes()->create([
            'tenant_id' => $customer->tenant_id,
            'user_id' => $request->user()?->id,
            'body' => $request->validated('body'),
        ]);

        return back()->with('success', 'Customer note added.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer): View
    {
        $this->authorizeTenantAccess($customer);

        return view('customers.edit', [
            'customer' => $customer,
            'organizations' => Organization::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse|RedirectResponse
    {
        $this->authorizeTenantAccess($customer);

        $validated = $request->validated();

        // Update name based on customer type
        if ($validated['customer_type'] === 'individual') {
            $validated['name'] = trim($validated['first_name'].' '.$validated['last_name']);
            $validated['company_name'] = null;
        } else {
            $validated['name'] = $validated['company_name'];
            $validated['first_name'] = null;
            $validated['last_name'] = null;
            $validated['national_id'] = null;
        }

        $validated['billing_disabled_at'] = $validated['billing_enabled'] ? null : ($customer->billing_disabled_at ?? now());

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $customer->update($validated);

        if (! $request->expectsJson()) {
            return redirect()
                ->route('customers.show', $customer)
                ->with('success', 'Customer updated successfully.');
        }

        return response()->json([
            'message' => 'Customer updated successfully.',
            'customer' => $customer->fresh(),
        ]);
    }

    public function updateNotifications(NotificationPreferenceRequest $request, Customer $customer, NotificationPreferenceService $preferences): RedirectResponse
    {
        $this->authorizeTenantAccess($customer);
        $preferences->updateFor($customer, $request->validated());

        return back()->with('success', "Notification preferences updated for {$customer->full_name}.");
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

    public function updateBilling(Request $request, Customer $customer): JsonResponse|RedirectResponse
    {
        $this->authorizeTenantAccess($customer);

        $validated = $request->validate([
            'billing_enabled' => 'required|boolean',
        ]);

        $customer->update([
            'billing_enabled' => $validated['billing_enabled'],
            'billing_disabled_at' => $validated['billing_enabled'] ? null : ($customer->billing_disabled_at ?? now()),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Customer billing settings updated successfully.',
                'customer' => $customer->fresh(),
            ]);
        }

        return back()->with('success', 'Customer billing settings updated successfully.');
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
