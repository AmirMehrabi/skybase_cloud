<?php

namespace App\Http\Controllers;

use App\Http\Requests\Plan\SavePlanRequest;
use App\Models\Plan;
use App\Models\UserGroup;
use App\Services\ActivityLogFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $plans = Plan::query()
            ->filter($request->only(['search', 'status', 'type', 'category', 'billing_cycle']))
            ->ordered()
            ->get()
            ->map(fn (Plan $plan) => $this->transformPlan($plan));

        return view('plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('plans.create', [
            'plan' => new Plan,
            'userGroups' => UserGroup::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(SavePlanRequest $request): RedirectResponse
    {
        $validated = $this->normalizedData($request);
        $validated['tenant_id'] = tenant_id() ?? $request->user()->tenant_id;
        $validated['user_group_id'] = $request->user()?->isOwner()
            ? ($validated['user_group_id'] ?? null)
            : $request->user()?->user_group_id;
        $plan = Plan::create($validated);

        return redirect()->route('plans.show', $plan)->with('success', 'Plan created successfully.');
    }

    public function show(Plan $plan): View
    {
        return view('plans.show', [
            'plan' => $this->transformPlan($plan),
            'activityLog' => app(ActivityLogFormatter::class)->forSubject($plan, auth()->user()?->tenant_id),
        ]);
    }

    public function edit(Plan $plan): View
    {
        return view('plans.edit', [
            'plan' => $plan,
            'userGroups' => UserGroup::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(SavePlanRequest $request, Plan $plan): RedirectResponse
    {
        $validated = $this->normalizedData($request);
        $validated['user_group_id'] = $request->user()?->isOwner()
            ? ($validated['user_group_id'] ?? null)
            : $plan->user_group_id;
        $plan->update($validated);

        return redirect()->route('plans.show', $plan)->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('plans.index')->with('success', 'Plan deleted successfully.');
    }

    /** @return array<string, mixed> */
    protected function normalizedData(SavePlanRequest $request): array
    {
        $validated = $request->validated();

        $validated['unlimited'] = $request->boolean('unlimited');
        $validated['contract_required'] = $request->boolean('contract_required');
        $validated['shaping_mode'] = $validated['shaping_mode'] ?? 'basic';
        $validated['burst_download'] = $validated['burst_download'] ?? 0;
        $validated['burst_upload'] = $validated['burst_upload'] ?? 0;
        $validated['data_cap_action'] = $validated['data_cap_action'] ?? 'none';
        $validated['setup_fee'] = $validated['setup_fee'] ?? 0;
        $validated['grace_period_days'] = $validated['grace_period_days'] ?? 7;
        $validated['priority'] = $validated['priority'] ?? 5;
        $validated['data_limit'] = $validated['unlimited'] ? null : ($validated['data_limit'] ?? null);
        $validated['data_cap_action'] = $validated['unlimited'] ? 'none' : $validated['data_cap_action'];
        $validated['throttle_download_speed'] = $validated['data_cap_action'] === 'throttle' ? ($validated['throttle_download_speed'] ?? null) : null;
        $validated['throttle_upload_speed'] = $validated['data_cap_action'] === 'throttle' ? ($validated['throttle_upload_speed'] ?? null) : null;
        $validated['contract_duration'] = $validated['contract_required'] ? ($validated['contract_duration'] ?? null) : null;

        return $validated;
    }

    protected function transformPlan(Plan $plan): array
    {
        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'internal_name' => $plan->internal_name,
            'description' => $plan->description,
            'status' => $plan->status,
            'visibility' => $plan->visibility,
            'type' => $plan->type,
            'category' => $plan->category,
            'download_speed' => $plan->download_speed,
            'upload_speed' => $plan->upload_speed,
            'burst_download' => $plan->burst_download,
            'burst_upload' => $plan->burst_upload,
            'bandwidth_unit' => $plan->bandwidth_unit,
            'shaping_mode' => $plan->shaping_mode ?? 'basic',
            'burst_threshold_download' => $plan->burst_threshold_download,
            'burst_threshold_upload' => $plan->burst_threshold_upload,
            'burst_time_download' => $plan->burst_time_download,
            'burst_time_upload' => $plan->burst_time_upload,
            'min_download_speed' => $plan->min_download_speed,
            'min_upload_speed' => $plan->min_upload_speed,
            'shaping_priority' => $plan->shaping_priority,
            'queue_type' => $plan->queue_type,
            'mikrotik_rate_limit' => $plan->mikrotikRateLimit(),
            'traffic_shaping_summary' => $plan->trafficShapingSummary(),
            'data_limit' => $plan->data_limit,
            'data_unit' => $plan->data_unit,
            'data_cap_action' => $plan->data_cap_action ?? 'none',
            'throttle_download_speed' => $plan->throttle_download_speed,
            'throttle_upload_speed' => $plan->throttle_upload_speed,
            'unlimited' => $plan->unlimited,
            'price' => (float) $plan->price,
            'currency' => $plan->currency,
            'billing_cycle' => $plan->billing_cycle,
            'grace_period_days' => $plan->grace_period_days,
            'setup_fee' => (float) $plan->setup_fee,
            'tax_profile' => $plan->tax_profile,
            'router_profile' => $plan->router_profile,
            'ip_pool' => $plan->ip_pool,
            'priority' => $plan->priority,
            'contract_required' => $plan->contract_required,
            'contract_duration' => $plan->contract_duration,
            'available_from' => $plan->available_from?->format('Y-m-d'),
            'available_to' => $plan->available_to?->format('Y-m-d'),
            'notes' => $plan->notes,
            'subscribers' => $plan->subscribers_count,
            'created_at' => $plan->created_at?->toDateTimeString(),
            'updated_at' => $plan->updated_at?->toDateTimeString(),
        ];
    }
}
