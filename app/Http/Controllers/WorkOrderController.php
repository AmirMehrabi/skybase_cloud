<?php

namespace App\Http\Controllers;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Http\Requests\WorkOrder\AssignWorkOrderRequest;
use App\Http\Requests\WorkOrder\ProvisionWorkOrderRequest;
use App\Http\Requests\WorkOrder\ScheduleWorkOrderRequest;
use App\Http\Requests\WorkOrder\StoreWorkOrderAttachmentRequest;
use App\Http\Requests\WorkOrder\StoreWorkOrderMaterialRequest;
use App\Http\Requests\WorkOrder\StoreWorkOrderNoteRequest;
use App\Http\Requests\WorkOrder\StoreWorkOrderRequest;
use App\Http\Requests\WorkOrder\TransitionWorkOrderRequest;
use App\Http\Requests\WorkOrder\UpdateWorkOrderRequest;
use App\Http\Requests\WorkOrder\UpdateWorkOrderTaskRequest;
use App\Models\AccessPoint;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\TicketTeam;
use App\Models\WorkOrder;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderTask;
use App\Services\WorkOrders\WorkOrderEventService;
use App\Services\WorkOrders\WorkOrderNumberService;
use App\Services\WorkOrders\WorkOrderProvisioningService;
use App\Services\WorkOrders\WorkOrderTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkOrderController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', WorkOrder::class);
        $user = $request->user();
        $query = WorkOrder::query()->with(['customer', 'subscription', 'assignedTeam', 'assignedUser']);

        if (! $user->hasPermission('work_orders.manage')) {
            $teamIds = $user->ticketTeams()->where('ticket_team_user.is_active', true)->pluck('ticket_teams.id');
            $query->where(fn ($query) => $query->where('assigned_user_id', $user->id)
                ->orWhere('created_by_user_id', $user->id)
                ->orWhereIn('assigned_team_id', $teamIds));
        }

        $workOrders = $query
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->input('priority')))
            ->when($request->filled('assigned'), fn ($query) => $request->input('assigned') === 'unassigned' ? $query->whereNull('assigned_user_id') : $query->where('assigned_user_id', $request->integer('assigned')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(fn ($query) => $query
                    ->where('work_order_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('contact_phone', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subscription', fn ($query) => $query->where('pppoe_username', 'like', "%{$search}%")));
            })
            ->orderByRaw('scheduled_start_at IS NULL')
            ->orderBy('scheduled_start_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $stats = WorkOrder::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('modules.work-orders.index', compact('workOrders', 'stats'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', WorkOrder::class);

        return view('modules.work-orders.create', $this->formData($request));
    }

    public function store(StoreWorkOrderRequest $request, WorkOrderNumberService $numbers, WorkOrderEventService $events): RedirectResponse
    {
        $validated = $request->validated();
        $tasks = Arr::pull($validated, 'tasks', []);
        $tenantId = (string) (tenant_id() ?? $request->user()->tenant_id);

        $workOrder = DB::transaction(function () use ($validated, $tasks, $tenantId, $request, $numbers, $events): WorkOrder {
            $workOrder = WorkOrder::create($validated + [
                'tenant_id' => $tenantId,
                'work_order_number' => $numbers->next($tenantId),
                'source' => $validated['source_ticket_id'] ?? null ? 'ticket' : 'manual',
                'status' => WorkOrderStatus::Draft,
                'created_by_user_id' => $request->user()->id,
            ]);

            foreach ($tasks ?: $this->defaultTasks($workOrder->type) as $index => $task) {
                WorkOrderTask::create([
                    'tenant_id' => $tenantId,
                    'work_order_id' => $workOrder->id,
                    'title' => $task['title'],
                    'instructions' => $task['instructions'] ?? null,
                    'is_required' => $task['is_required'] ?? true,
                    'sort_order' => $index,
                ]);
            }

            $events->record($workOrder, 'work_order.created', $request->user());

            return $workOrder;
        });

        return redirect()->route('work-orders.show', $workOrder)->with('success', "Work order {$workOrder->work_order_number} created.");
    }

    public function show(WorkOrder $workOrder): View
    {
        Gate::authorize('view', $workOrder);
        $workOrder->load([
            'customer', 'subscription.plan', 'sourceTicket', 'plan', 'router', 'accessPoint',
            'assignedTeam.users', 'assignedUser', 'createdBy', 'tasks', 'events.actor',
            'notes.user', 'attachments', 'appointments', 'materials',
        ]);

        return view('modules.work-orders.show', [
            'workOrder' => $workOrder,
            'teams' => TicketTeam::query()->active()->with(['users' => fn ($query) => $query->wherePivot('is_active', true)])->orderBy('name')->get(),
            'plans' => Plan::query()->where('status', 'active')->orderBy('name')->get(),
            'routers' => Router::query()->orderBy('name')->get(),
            'accessPoints' => AccessPoint::query()->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, WorkOrder $workOrder): View
    {
        Gate::authorize('update', $workOrder);
        if ($workOrder->status !== WorkOrderStatus::Draft) {
            abort(409, 'Only draft work orders can be edited.');
        }

        return view('modules.work-orders.edit', $this->formData($request) + ['workOrder' => $workOrder->load('tasks')]);
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder, WorkOrderEventService $events): RedirectResponse
    {
        if ($workOrder->status !== WorkOrderStatus::Draft) {
            throw ValidationException::withMessages(['status' => 'Only draft work orders can be edited.']);
        }

        $validated = Arr::except($request->validated(), ['tasks']);
        $old = $workOrder->only(array_keys($validated));
        $workOrder->update($validated);
        $events->record($workOrder, 'work_order.updated', $request->user(), $old, $validated);

        return redirect()->route('work-orders.show', $workOrder)->with('success', 'Work order updated.');
    }

    public function assign(AssignWorkOrderRequest $request, WorkOrder $workOrder, WorkOrderEventService $events): RedirectResponse
    {
        $old = $workOrder->only(['assigned_team_id', 'assigned_user_id']);
        $workOrder->update($request->validated());
        $events->record($workOrder, 'work_order.assigned', $request->user(), $old, $request->validated());

        return back()->with('success', 'Assignment updated.');
    }

    public function schedule(ScheduleWorkOrderRequest $request, WorkOrder $workOrder, WorkOrderEventService $events): RedirectResponse
    {
        if (! $workOrder->assigned_user_id) {
            throw ValidationException::withMessages(['assigned_user_id' => 'Assign a technician before scheduling.']);
        }

        $validated = $request->validated();
        DB::transaction(function () use ($workOrder, $validated, $request, $events): void {
            if ($workOrder->appointments()->exists()) {
                $workOrder->appointments()->where('status', 'scheduled')->update(['status' => 'rescheduled']);
            }
            $workOrder->appointments()->create([
                'tenant_id' => $workOrder->tenant_id,
                'assigned_user_id' => $workOrder->assigned_user_id,
                'status' => 'scheduled',
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'notes' => $validated['notes'] ?? null,
                'reschedule_reason' => $validated['reschedule_reason'] ?? null,
            ]);
            $workOrder->update(['scheduled_start_at' => $validated['starts_at'], 'scheduled_end_at' => $validated['ends_at']]);
            $events->record($workOrder, 'work_order.scheduled', $request->user(), null, Arr::only($validated, ['starts_at', 'ends_at']));
        });

        return back()->with('success', 'Appointment scheduled.');
    }

    public function transition(TransitionWorkOrderRequest $request, WorkOrder $workOrder, WorkOrderTransitionService $transitions): RedirectResponse
    {
        $transitions->transition($workOrder, WorkOrderStatus::from($request->validated('status')), $request->user(), $request->validated());

        return back()->with('success', 'Work order status updated.');
    }

    public function updateTask(UpdateWorkOrderTaskRequest $request, WorkOrder $workOrder, WorkOrderTask $task, WorkOrderEventService $events): RedirectResponse
    {
        $status = $request->validated('status');
        $task->update([
            'status' => $status,
            'result' => $request->validated('result'),
            'completed_by_user_id' => $status === 'completed' ? $request->user()->id : null,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);
        $events->record($workOrder, 'work_order.task_updated', $request->user(), null, ['task_id' => $task->id, 'status' => $status]);

        return back()->with('success', 'Checklist updated.');
    }

    public function storeNote(StoreWorkOrderNoteRequest $request, WorkOrder $workOrder, WorkOrderEventService $events): RedirectResponse
    {
        $note = $workOrder->notes()->create(['tenant_id' => $workOrder->tenant_id, 'user_id' => $request->user()->id, 'body' => $request->validated('body')]);
        $events->record($workOrder, 'work_order.note_added', $request->user(), null, ['note_id' => $note->id]);

        return back()->with('success', 'Internal note added.');
    }

    public function storeAttachment(StoreWorkOrderAttachmentRequest $request, WorkOrder $workOrder, WorkOrderEventService $events): RedirectResponse
    {
        $file = $request->file('attachment');
        $path = $file->store("work-orders/{$workOrder->tenant_id}/{$workOrder->work_order_number}", 'local');
        $attachment = $workOrder->attachments()->create([
            'tenant_id' => $workOrder->tenant_id, 'uploaded_by_user_id' => $request->user()->id,
            'category' => $request->validated('category'), 'original_name' => $file->getClientOriginalName(),
            'disk' => 'local', 'path' => $path, 'mime_type' => $file->getMimeType(), 'size' => $file->getSize() ?: 0,
        ]);
        $events->record($workOrder, 'work_order.attachment_added', $request->user(), null, ['attachment_id' => $attachment->id]);

        return back()->with('success', 'Evidence uploaded.');
    }

    public function storeMaterial(StoreWorkOrderMaterialRequest $request, WorkOrder $workOrder, WorkOrderEventService $events): RedirectResponse
    {
        $material = $workOrder->materials()->create($request->validated() + ['tenant_id' => $workOrder->tenant_id]);
        $events->record($workOrder, 'work_order.material_recorded', $request->user(), null, ['material_id' => $material->id, 'direction' => $material->direction]);

        return back()->with('success', 'Material movement recorded.');
    }

    public function downloadAttachment(WorkOrder $workOrder, WorkOrderAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $workOrder);
        abort_unless((int) $attachment->work_order_id === (int) $workOrder->id && (string) $attachment->tenant_id === (string) $workOrder->tenant_id, 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function provision(ProvisionWorkOrderRequest $request, WorkOrder $workOrder, WorkOrderProvisioningService $provisioning): RedirectResponse
    {
        $subscription = $provisioning->provision($workOrder, $request->user(), $request->validated());

        return redirect()->route('work-orders.show', $workOrder)->with('success', "Subscription {$subscription->subscription_code} provisioned.");
    }

    /** @return array<string, mixed> */
    private function formData(Request $request): array
    {
        $tenantId = tenant_id() ?? $request->user()?->tenant_id;

        return [
            'customers' => Customer::query()->where('tenant_id', $tenantId)->orderBy('name')->get(),
            'subscriptions' => Subscription::query()->where('tenant_id', $tenantId)->with('customer')->latest()->get(),
            'plans' => Plan::query()->where('status', 'active')->orderBy('name')->get(),
            'types' => WorkOrderType::cases(),
            'priorities' => WorkOrderPriority::cases(),
            'teams' => TicketTeam::query()
                ->active()
                ->with(['users' => fn ($query) => $query
                    ->where('users.status', 'active')
                    ->wherePivot('is_active', true)
                    ->orderBy('users.name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ];
    }

    /** @return list<array{title: string, is_required: bool}> */
    private function defaultTasks(WorkOrderType $type): array
    {
        if ($type === WorkOrderType::NewInstallation) {
            return [
                ['title' => 'Confirm service address and customer contact', 'is_required' => true],
                ['title' => 'Verify coverage, capacity, and line of sight', 'is_required' => true],
                ['title' => 'Install and record customer equipment', 'is_required' => true],
                ['title' => 'Test signal, throughput, and connectivity', 'is_required' => true],
                ['title' => 'Obtain customer acceptance', 'is_required' => true],
            ];
        }

        return [
            ['title' => 'Confirm scope and safety requirements', 'is_required' => true],
            ['title' => 'Perform assigned work', 'is_required' => true],
            ['title' => 'Record test results and customer outcome', 'is_required' => true],
        ];
    }
}
