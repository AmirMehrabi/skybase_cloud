<?php

namespace App\Http\Controllers;

use App\Http\Requests\Acs\AssignDeviceRequest;
use App\Http\Requests\Acs\DeviceActionRequest;
use App\Http\Requests\Acs\SaveConnectionRequest;
use App\Http\Requests\Acs\SaveProfileRequest;
use App\Jobs\RunAcsAction;
use App\Jobs\SyncAcsInventory;
use App\Models\AcsConnection;
use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\DeviceAction;
use App\Models\ProvisioningProfile;
use App\Models\Subscription;
use App\Services\AcsClient;
use App\Services\AcsProvisioningService;
use App\Services\AcsService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcsController extends Controller
{
    public function index(Request $request, AcsService $service): View
    {
        $devices = $service->visibleDevices()->with(['customer', 'subscription'])->when($request->filled('subscription'), fn ($q) => $q->where('subscription_id', $request->integer('subscription')))->when($request->filled('customer'), fn ($q) => $q->where('customer_id', $request->integer('customer')))->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request): void {
            $search = '%'.substr($request->string('search'), 0, 100).'%';
            $q->where('serial_number', 'like', $search)->orWhere('model', 'like', $search);
        }))->latest('last_contact_at')->paginate(20)->withQueryString();

        return view('modules.acs.index', ['devices' => $devices, 'connection' => AcsConnection::query()->first()]);
    }

    public function settings(): View
    {
        abort_unless(auth()->user()->isOwner(), 403);

        return view('modules.acs.settings', ['connection' => AcsConnection::query()->first(), 'endpoints' => array_combine(config('acs.allowed_endpoints'), config('acs.allowed_endpoints'))]);
    }

    public function save(SaveConnectionRequest $request): RedirectResponse
    {
        $connection = AcsConnection::query()->first();
        $data = $request->validated();
        if ($connection && $connection->base_url !== $data['base_url']) {
            throw ValidationException::withMessages(['base_url' => 'Changing an enrolled ACS requires administrator-assisted migration.']);
        }
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        try {
            AcsConnection::query()->updateOrCreate(['tenant_id' => tenant()->id], $data + ['health' => 'untested']);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['base_url' => 'This endpoint is already assigned. Use a dedicated tenant endpoint.']);
        }

        return back()->with('success', 'Connection saved. Test it, then discover your equipment.');
    }

    public function test(AcsClient $client): RedirectResponse
    {
        abort_unless(auth()->user()->isOwner(), 403);
        $connection = AcsConnection::query()->firstOrFail();
        try {
            $client->records($connection, 'devices', [], ['limit' => 1, 'projection' => '_id']);
            $connection->update(['health' => 'connected', 'last_tested_at' => now()]);

            return back()->with('success', 'GenieACS is reachable. You can now discover devices.');
        } catch (\Throwable) {
            $connection->update(['health' => 'unreachable', 'last_tested_at' => now()]);

            return back()->with('error', 'Cannot reach GenieACS. Check the approved endpoint, authentication and service status.');
        }
    }

    public function sync(): RedirectResponse
    {
        abort_unless(auth()->user()->isOwner(), 403);
        AcsConnection::query()->firstOrFail();
        if (Cache::add('acs-sync-request:'.tenant()->id, true, 30)) {
            SyncAcsInventory::dispatch(tenant()->id)->afterCommit();
        }

        return back()->with('success', 'Discovery queued. Devices appear after synchronization; refresh this page shortly.');
    }

    public function show(CustomerDevice $device, AcsService $service): View
    {
        $device = $service->visibleDevices()->with(['connection', 'customer', 'subscription'])->findOrFail($device->id);

        return view('modules.acs.show', ['device' => $device, 'customers' => Customer::query()->get()->mapWithKeys(fn ($customer) => [$customer->id => $customer->full_name]), 'subscriptions' => Subscription::query()->get()->map(fn ($subscription) => ['id' => $subscription->id, 'customer_id' => $subscription->customer_id, 'label' => $subscription->subscription_code.' · '.$subscription->name])->values()->all(), 'actions' => $device->actions()->with('actor')->latest()->limit(30)->get()]);
    }

    public function activity(CustomerDevice $device, AcsService $service): JsonResponse
    {
        $device = $service->visibleDevices()->findOrFail($device->id);

        return response()->json(['actions' => $device->actions()->with('actor')->latest()->limit(30)->get()->map(fn ($action) => ['id' => $action->id, 'requested' => $action->created_at->diffForHumans(), 'operation' => $action->operation, 'actor' => $action->actor?->name ?? 'System', 'status' => $action->status, 'message' => $action->message])]);
    }

    public function assign(AssignDeviceRequest $request, CustomerDevice $device, AcsService $service): RedirectResponse
    {
        $device = $service->visibleDevices()->findOrFail($device->id);
        $customer = $request->filled('customer_id') ? Customer::query()->findOrFail($request->integer('customer_id')) : null;
        $subscription = $request->filled('subscription_id') ? Subscription::query()->findOrFail($request->integer('subscription_id')) : null;
        if ($subscription && (! $customer || $subscription->customer_id !== $customer->id)) {
            throw ValidationException::withMessages(['subscription_id' => 'Choose a subscription belonging to the selected customer.']);
        }
        $device->getConnection()->transaction(function () use ($device, $customer, $subscription): void {
            $device = CustomerDevice::query()->lockForUpdate()->findOrFail($device->id);
            if ($device->actions()->whereIn('status', ['queued', 'submitting', 'pending', 'uncertain'])->exists()) {
                throw ValidationException::withMessages(['customer_id' => 'Resolve outstanding device actions before changing its assignment.']);
            }
            $device->actions()->create(['tenant_id' => tenant()->id, 'user_id' => auth()->id(), 'operation' => 'assignment', 'status' => 'completed', 'message' => 'Assignment changed from customer '.($device->customer_id ?? 'none').' / subscription '.($device->subscription_id ?? 'none').' to customer '.($customer?->id ?? 'none').' / subscription '.($subscription?->id ?? 'none').'.', 'finished_at' => now()]);
            $device->update(['customer_id' => $customer?->id, 'subscription_id' => $subscription?->id]);
        });

        return back()->with('success', 'Device assignment saved. No configuration was changed.');
    }

    public function profile(SaveProfileRequest $request, CustomerDevice $device, AcsService $service): RedirectResponse
    {
        $device = $service->visibleDevices()->findOrFail($device->id);
        $parameters = collect($request->validated('parameters'))->filter(fn ($row) => filled($row['path'] ?? null))->values()->all();
        if ($parameters === []) {
            throw ValidationException::withMessages(['parameters' => 'Choose at least one writable parameter.']);
        }
        foreach ($parameters as $parameter) {
            if (! isset($device->capabilities[$parameter['path']]) || ! in_array($device->capabilities[$parameter['path']], ['xsd:string'], true) || preg_match('/ManagementServer|X_MIKROTIK.*(Script|Command)|Password.*ACS/i', $parameter['path'])) {
                throw ValidationException::withMessages(['parameters' => 'Choose a supported string parameter outside ACS management settings.']);
            }
        }
        $profile = ProvisioningProfile::query()->create(['tenant_id' => tenant()->id, 'name' => $request->validated('name'), 'manufacturer' => $device->manufacturer ?? '', 'model' => $device->model ?? '', 'parameters' => $parameters]);
        $device->update(['provisioning_profile_id' => $profile->id]);

        return back()->with('success', 'Profile saved. Apply it explicitly to test before enabling automation.');
    }

    public function provision(Request $request, CustomerDevice $device, AcsService $service, AcsProvisioningService $provisioning): RedirectResponse
    {
        abort_unless($request->user()->isOwner(), 403);
        $device = $service->visibleDevices()->findOrFail($device->id);
        $action = $provisioning->queue($device, auth()->id());

        return back()->with($action ? 'success' : 'error', $action ? 'Provisioning queued. Track its result below.' : 'Assign an active subscription and a compatible profile, and resolve outstanding actions first.');
    }

    public function action(DeviceActionRequest $request, CustomerDevice $device, AcsService $service): RedirectResponse
    {
        $device = $service->visibleDevices()->findOrFail($device->id);
        $operation = $request->validated('operation');
        $task = match ($operation) {
            'refresh' => ['name' => 'refreshObject', 'objectName' => ''], 'reboot' => ['name' => 'reboot'], default => ['name' => 'setParameterValues', 'parameterValues' => [[$request->validated('ssid_path'), $request->validated('ssid'), 'xsd:string']]]
        };
        if ($operation === 'wifi' && (! preg_match('/^(Device\.WiFi\.SSID\.\d+\.SSID|InternetGatewayDevice\.LANDevice\.\d+\.WLANConfiguration\.\d+\.SSID)$/', $request->string('ssid_path')) || ! isset($device->capabilities[$request->string('ssid_path')->toString()]))) {
            throw ValidationException::withMessages(['ssid_path' => 'Refresh the device first and choose a supported Wi-Fi interface.']);
        }
        $action = $device->getConnection()->transaction(function () use ($device, $operation, $task): DeviceAction {
            $device = CustomerDevice::query()->lockForUpdate()->findOrFail($device->id);
            if ($device->actions()->whereIn('status', ['queued', 'submitting', 'pending', 'uncertain'])->exists()) {
                throw ValidationException::withMessages(['operation' => 'A device operation is already outstanding. Wait for completion before submitting another.']);
            }

            return $device->actions()->create(['tenant_id' => tenant()->id, 'user_id' => auth()->id(), 'operation' => $operation, 'payload' => $task]);
        });
        RunAcsAction::dispatch(tenant()->id, $action->id)->afterCommit();

        return back()->with('success','Operation queued. Track its progress in Device activity.');
    }
}
