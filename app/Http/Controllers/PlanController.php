<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\ActivityLogFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        return view('plans.create', ['plan' => new Plan]);
    }

    public function store(Request $request): RedirectResponse
    {
        $plan = Plan::create($this->validatedData($request));

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
        return view('plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validatedData($request, $plan));

        return redirect()->route('plans.show', $plan)->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('plans.index')->with('success', 'Plan deleted successfully.');
    }

    protected function validatedData(Request $request, ?Plan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'internal_name' => ['required', 'string', 'max:255', Rule::unique('plans', 'internal_name')->ignore($plan?->id)],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive', 'archived'])],
            'visibility' => ['required', Rule::in(['public', 'private', 'hidden'])],
            'type' => ['required', Rule::in(['pppoe', 'hotspot', 'static', 'dhcp', 'fiber', 'wireless'])],
            'category' => ['nullable', 'string', 'max:255'],
            'download_speed' => ['required', 'integer', 'min:0'],
            'upload_speed' => ['required', 'integer', 'min:0'],
            'burst_download' => ['nullable', 'integer', 'min:0'],
            'burst_upload' => ['nullable', 'integer', 'min:0'],
            'bandwidth_unit' => ['required', Rule::in(['Kbps', 'Mbps', 'Gbps'])],
            'shaping_mode' => ['nullable', Rule::in(['basic', 'advanced', 'disabled'])],
            'burst_threshold_download' => ['nullable', 'integer', 'min:0'],
            'burst_threshold_upload' => ['nullable', 'integer', 'min:0'],
            'burst_time_download' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'burst_time_upload' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'min_download_speed' => ['nullable', 'integer', 'min:0', 'lte:download_speed'],
            'min_upload_speed' => ['nullable', 'integer', 'min:0', 'lte:upload_speed'],
            'shaping_priority' => ['nullable', 'integer', 'min:1', 'max:8'],
            'queue_type' => ['nullable', 'string', 'max:255'],
            'data_limit' => ['nullable', 'integer', 'min:0'],
            'data_unit' => ['required', Rule::in(['MB', 'GB', 'TB'])],
            'data_cap_action' => ['nullable', Rule::in(['none', 'notify', 'throttle', 'suspend'])],
            'throttle_download_speed' => ['nullable', 'integer', 'min:0'],
            'throttle_upload_speed' => ['nullable', 'integer', 'min:0'],
            'unlimited' => ['nullable', 'boolean'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'billing_cycle' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'tax_profile' => ['nullable', 'string', 'max:255'],
            'router_profile' => ['nullable', 'string', 'max:255'],
            'ip_pool' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'contract_required' => ['nullable', 'boolean'],
            'contract_duration' => ['nullable', 'integer', 'min:1'],
            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],
            'notes' => ['nullable', 'string'],
        ]);

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
