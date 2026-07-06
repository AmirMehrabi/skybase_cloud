@extends('layouts.admin')

@section('title', 'Work Orders')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-950">Work Orders</h1>
            <p class="mt-1 text-sm text-slate-600">Schedule and execute installations, field service, and network operations.</p>
        </div>
        @can('create', App\Models\WorkOrder::class)
            <a href="{{ route('work-orders.create') }}" class="inline-flex items-center justify-center rounded-lg bg-[#0d2f35] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#123f3d]">New work order</a>
        @endcan
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Open work', 'value' => collect($stats)->except(['completed', 'cancelled'])->sum(), 'tone' => 'text-slate-950'],
            ['label' => 'Scheduled', 'value' => $stats['scheduled'] ?? 0, 'tone' => 'text-blue-700'],
            ['label' => 'Blocked', 'value' => $stats['blocked'] ?? 0, 'tone' => 'text-amber-700'],
            ['label' => 'Ready to activate', 'value' => $stats['ready_for_activation'] ?? 0, 'tone' => 'text-emerald-700'],
        ] as $stat)
            <div class="border-l-4 border-[#0d2f35] bg-white px-5 py-4 shadow-sm ring-1 ring-slate-900/10">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-bold {{ $stat['tone'] }}">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" class="grid gap-3 border border-slate-900/10 bg-white p-4 shadow-sm md:grid-cols-5">
        <input name="search" value="{{ request('search') }}" placeholder="Number, customer, phone, PPPoE" class="rounded-lg border border-slate-300 px-3 py-2 text-sm md:col-span-2">
        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach(App\Enums\WorkOrderStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ str($status->value)->replace('_', ' ')->headline() }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All work types</option>
            @foreach(App\Enums\WorkOrderType::cases() as $type)
                <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ str($type->value)->replace('_', ' ')->headline() }}</option>
            @endforeach
        </select>
        <button class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Filter queue</button>
    </form>

    <div class="overflow-hidden border border-slate-900/10 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Work order</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Schedule</th><th class="px-4 py-3">Owner</th><th class="px-4 py-3">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($workOrders as $workOrder)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4"><a class="font-bold text-[#0d2f35]" href="{{ route('work-orders.show', $workOrder) }}">{{ $workOrder->work_order_number }}</a><p class="mt-1 max-w-sm truncate text-slate-600">{{ $workOrder->title }}</p><p class="mt-1 text-xs text-slate-500">{{ str($workOrder->type->value)->replace('_', ' ')->headline() }} · {{ ucfirst($workOrder->priority->value) }}</p></td>
                            <td class="px-4 py-4"><p class="font-medium text-slate-900">{{ $workOrder->customer?->full_name }}</p><p class="text-xs text-slate-500">{{ $workOrder->subscription?->pppoe_username ?: 'Pre-service' }}</p></td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $workOrder->scheduled_start_at?->format('M d, Y H:i') ?? 'Not scheduled' }}</td>
                            <td class="px-4 py-4"><p>{{ $workOrder->assignedUser?->name ?? 'Unassigned' }}</p><p class="text-xs text-slate-500">{{ $workOrder->assignedTeam?->name }}</p></td>
                            <td class="px-4 py-4"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str($workOrder->status->value)->replace('_', ' ')->headline() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-14 text-center text-slate-500">No work orders match this queue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $workOrders->links() }}
</div>
@endsection
