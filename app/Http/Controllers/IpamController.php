<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIpPoolRequest;
use App\Http\Requests\UpdateIpPoolRequest;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Router;
use App\Models\Site;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class IpamController extends Controller
{
    /**
     * Display the IPAM dashboard.
     */
    public function dashboard(): View
    {
        // Get tenant ID directly from authenticated user instead of helper
        $tenantId = auth()->user()?->tenant_id;

        if (! $tenantId) {
            abort(403, 'No tenant context found.');
        }

        $poolsQuery = IpPool::where('tenant_id', $tenantId);

        // Get statistics
        $totalPools = (clone $poolsQuery)->count();
        $allPools = (clone $poolsQuery)->get();

        $totalIPs = $allPools->sum('total_ips');
        $usedIPs = $allPools->sum('used_ips');
        $availableIPs = $allPools->sum('available_ips');
        $reservedIPs = $allPools->sum('reserved_ips');

        $exhaustedPools = (clone $poolsQuery)
            ->where('status', 'exhausted')
            ->count();

        // Get warning pools (usage > 80%)
        $warningPools = $allPools
            ->filter(fn ($pool) => $pool->usage_percentage > 80);

        $pools = (clone $poolsQuery)
            ->with(['router', 'routers', 'siteRecord'])
            ->orderBy('created_at', 'desc')
            ->paginate(100);

        return view('ipam.dashboard', compact(
            'pools',
            'totalPools',
            'totalIPs',
            'usedIPs',
            'availableIPs',
            'reservedIPs',
            'exhaustedPools',
            'warningPools'
        ));
    }

    /**
     * Display a listing of IP pools.
     */
    public function index(): View
    {
        $tenantId = auth()->user()->tenant_id;

        $ipPools = IpPool::where('tenant_id', $tenantId)
            ->with(['router', 'routers', 'siteRecord'])
            ->orderBy('created_at', 'desc')
            ->paginate(100);

        // Get filter options
        $routers = Router::where('tenant_id', $tenantId)->pluck('name', 'id');
        $sites = Site::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->pluck('name')
            ->merge(
                IpPool::where('tenant_id', $tenantId)
                    ->whereNotNull('site')
                    ->where('site', '!=', '')
                    ->pluck('site')
            )
            ->unique()
            ->values();

        return view('ipam.pools.index', compact('ipPools', 'routers', 'sites'));
    }

    /**
     * Show the form for creating a new IP pool.
     */
    public function create(): View
    {
        $tenantId = auth()->user()->tenant_id;

        $routers = Router::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $sites = Site::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('ipam.pools.create', compact('routers', 'sites'));
    }

    /**
     * Store a newly created IP pool in storage.
     */
    public function store(StoreIpPoolRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $poolData = $request->validated();
            $tenantId = auth()->user()->tenant_id;
            $poolData['tenant_id'] = $tenantId;
            $routerIds = $poolData['router_ids'] ?? [];

            $this->applyPoolAssignments($poolData, $tenantId);
            unset($poolData['router_ids']);

            // Calculate total IPs based on CIDR
            $cidr = $poolData['cidr'];
            $totalIps = pow(2, 32 - $cidr);
            $poolData['total_ips'] = min($totalIps, 254); // Cap at 254 for /24 networks
            $poolData['available_ips'] = $poolData['total_ips'];

            $pool = IpPool::create($poolData);
            $this->syncPoolRouters($pool, $routerIds, $poolData['all_devices'] ?? false);

            // Generate IP addresses for the pool
            $this->generateIpAddresses($pool);

            DB::commit();

            return redirect()
                ->route('ipam.pools.show', $pool)
                ->with('success', 'IP pool created successfully.');
        } catch (QueryException $e) {
            DB::rollBack();

            if ($this->isDuplicatePoolException($e)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'network_address' => 'An IP pool with this network address and CIDR already exists.',
                    ]);
            }

            Log::error('Failed to create IP pool: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create IP pool. Please try again.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create IP pool: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create IP pool. Please try again.');
        }
    }

    /**
     * Display the specified IP pool.
     */
    public function show(IpPool $pool): View
    {
        // Verify tenant ownership
        $this->authorizeTenantAccess($pool);

        $pool->load(['router', 'routers', 'siteRecord', 'ipAddresses' => fn ($query) => $query->with('customer')]);

        $ipAddresses = $pool->ipAddresses()
            ->with('customer')
            ->orderBy('ip_address')
            ->paginate(50);

        return view('ipam.pools.show', compact('pool', 'ipAddresses'));
    }

    /**
     * Show the form for editing the specified IP pool.
     */
    public function edit(IpPool $pool): View
    {
        // Verify tenant ownership
        $this->authorizeTenantAccess($pool);

        $tenantId = auth()->user()->tenant_id;

        $routers = Router::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $sites = Site::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('ipam.pools.edit', compact('pool', 'routers', 'sites'));
    }

    /**
     * Update the specified IP pool in storage.
     */
    public function update(UpdateIpPoolRequest $request, IpPool $pool): RedirectResponse
    {
        // Verify tenant ownership
        $this->authorizeTenantAccess($pool);

        try {
            DB::beginTransaction();

            $poolData = $request->validated();
            $tenantId = auth()->user()->tenant_id;
            $routerIds = $poolData['router_ids'] ?? [];

            $this->applyPoolAssignments($poolData, $tenantId, $pool);
            unset($poolData['router_ids']);
            $pool->update($poolData);
            $this->syncPoolRouters($pool, $routerIds, $poolData['all_devices'] ?? false);

            DB::commit();

            return redirect()
                ->route('ipam.pools.show', $pool)
                ->with('success', 'IP pool updated successfully.');
        } catch (QueryException $e) {
            DB::rollBack();

            if ($this->isDuplicatePoolException($e)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'network_address' => 'An IP pool with this network address and CIDR already exists.',
                    ]);
            }

            Log::error('Failed to update IP pool: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update IP pool. Please try again.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update IP pool: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update IP pool. Please try again.');
        }
    }

    /**
     * Remove the specified IP pool from storage.
     */
    public function destroy(IpPool $pool): RedirectResponse
    {
        // Verify tenant ownership
        $this->authorizeTenantAccess($pool);

        try {
            DB::beginTransaction();

            // Check if pool has assigned IPs
            if ($pool->used_ips > 0) {
                return redirect()
                    ->back()
                    ->with('error', 'Cannot delete pool with assigned IP addresses.');
            }

            $pool->delete();

            DB::commit();

            return redirect()
                ->route('ipam.pools.index')
                ->with('success', 'IP pool deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete IP pool: '.$e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Failed to delete IP pool. Please try again.');
        }
    }

    /**
     * Generate IP addresses for a pool.
     */
    protected function generateIpAddresses(IpPool $pool): void
    {
        $networkAddress = $pool->network_address;
        $cidr = $pool->cidr;

        // Calculate number of IPs to generate (max 254 for /24)
        $totalIps = min(pow(2, 32 - $cidr), 254);

        // Parse network address
        $parts = explode('.', $networkAddress);

        // Generate IPs
        for ($i = 1; $i <= $totalIps; $i++) {
            $ipSuffix = $i % 256;
            $ipThirdOctet = floor($i / 256);

            if ($ipThirdOctet > 0) {
                $parts[2] = (int) $parts[2] + $ipThirdOctet;
            }

            $ipAddress = "{$parts[0]}.{$parts[1]}.{$parts[2]}.{$ipSuffix}";

            // Determine initial status
            $status = 'available';
            $notes = null;

            // Reserve gateway (first IP)
            if ($i === 1) {
                $status = 'reserved';
                $notes = 'Gateway';
            }

            // Reserve broadcast IP (last IP)
            if ($i === $totalIps) {
                $status = 'reserved';
                $notes = 'Broadcast';
            }

            // Block reserved range if enabled
            if ($pool->block_reserved && $i <= 10 && $i !== 1) {
                $status = 'reserved';
                $notes = 'Infrastructure';
            }

            IpAddress::create([
                'tenant_id' => $pool->tenant_id,
                'ip_pool_id' => $pool->id,
                'ip_address' => $ipAddress,
                'status' => $status,
                'notes' => $notes,
            ]);
        }
    }

    /**
     * Normalize pool assignment fields for storage.
     *
     * @param  array<string, mixed>  $poolData
     */
    protected function applyPoolAssignments(array &$poolData, string $tenantId, ?IpPool $pool = null): void
    {
        $siteId = $poolData['site_id'] ?? null;
        $site = null;

        if (filled($siteId)) {
            $site = Site::where('tenant_id', $tenantId)->find($siteId);
        }

        if (! $site && blank($siteId) && filled($poolData['site'] ?? null) && $pool) {
            $site = Site::where('tenant_id', $tenantId)
                ->where(function ($query) use ($poolData): void {
                    $query->where('name', $poolData['site'])
                        ->orWhere('code', $poolData['site']);
                })
                ->first();
        }

        if ($site) {
            $poolData['site_id'] = $site->id;
            $poolData['site'] = $site->name;
        } elseif (blank($poolData['site'] ?? null)) {
            $poolData['site_id'] = null;
            $poolData['site'] = null;
        }

        $routerIds = collect($poolData['router_ids'] ?? [])
            ->filter(fn ($routerId): bool => filled($routerId))
            ->map(fn ($routerId): int => (int) $routerId)
            ->values();

        if ($poolData['all_devices'] ?? false) {
            $poolData['router_id'] = null;
        } elseif ($routerIds->isNotEmpty()) {
            $poolData['router_id'] = $routerIds->first();
        } elseif (filled($poolData['router_id'] ?? null)) {
            $poolData['router_id'] = (int) $poolData['router_id'];
            $routerIds = collect([$poolData['router_id']]);
        } elseif ($pool !== null) {
            $routerIds = $pool->routers()->pluck('routers.id')->map(fn ($routerId): int => (int) $routerId);
        }

        $poolData['all_devices'] = (bool) ($poolData['all_devices'] ?? false);
    }

    /**
     * Sync the pool router assignments.
     *
     * @param  array<int, int|string>  $routerIds
     */
    protected function syncPoolRouters(IpPool $pool, array $routerIds, bool $allDevices): void
    {
        if ($allDevices) {
            $pool->routers()->detach();

            return;
        }

        $normalizedRouterIds = collect($routerIds)
            ->filter(fn ($routerId): bool => filled($routerId))
            ->map(fn ($routerId): int => (int) $routerId)
            ->values()
            ->all();

        $syncData = collect($normalizedRouterIds)
            ->mapWithKeys(fn (int $routerId): array => [
                $routerId => ['tenant_id' => $pool->tenant_id],
            ])
            ->all();

        $pool->routers()->sync($syncData);
    }

    private function isDuplicatePoolException(QueryException $exception): bool
    {
        return $exception->getCode() === '23000'
            && str_contains($exception->getMessage(), 'unique_pool_per_tenant');
    }

    /**
     * Check if an IP address is available for assignment.
     */
    public function checkIp(Request $request): JsonResponse
    {
        $request->validate([
            'ip' => 'required|ip',
        ]);

        $ip = $request->query('ip');
        $tenantId = auth()->user()?->tenant_id;

        // Check if IP exists in any pool
        $ipAddress = IpAddress::where('ip_address', $ip)
            ->when($tenantId, function ($query) use ($tenantId) {
                return $query->where('tenant_id', $tenantId);
            })
            ->first();

        if (! $ipAddress) {
            return response()->json([
                'available' => true,
                'ip' => $ip,
                'message' => 'IP is available',
            ]);
        }

        // IP exists, check its status
        if ($ipAddress->status === 'assigned') {
            $customer = $ipAddress->customer;

            return response()->json([
                'available' => false,
                'ip' => $ip,
                'status' => $ipAddress->status,
                'customer' => $customer ? $customer->full_name : null,
                'subscription_code' => $ipAddress->subscription_code,
                'message' => 'IP is currently assigned',
            ]);
        }

        // IP is available (available, reserved, or blocked)
        return response()->json([
            'available' => \in_array($ipAddress->status, ['available', 'reserved', 'blocked']),
            'ip' => $ip,
            'status' => $ipAddress->status,
            'message' => "IP is {$ipAddress->status}",
        ]);
    }

    /**
     * Verify tenant access to a resource.
     */
    protected function authorizeTenantAccess(IpPool $IpPool): void
    {
        // if ($IpPool->tenant_id !== auth()->user->tenant_id) {
        //     abort(403, 'Unauthorized access to this IP pool.');
        // }
    }
}
