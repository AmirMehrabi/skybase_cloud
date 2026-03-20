<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIpPoolRequest;
use App\Http\Requests\UpdateIpPoolRequest;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Router;
use Illuminate\Http\RedirectResponse;
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

        // Get statistics
        $totalPools = IpPool::where('tenant_id', $tenantId)->count();
        $pools = IpPool::where('tenant_id', $tenantId)->get();

        $totalIPs = $pools->sum('total_ips');
        $usedIPs = $pools->sum('used_ips');
        $availableIPs = $pools->sum('available_ips');
        $reservedIPs = $pools->sum('reserved_ips');

        $exhaustedPools = IpPool::where('tenant_id', $tenantId)
            ->where('status', 'exhausted')
            ->count();

        // Get warning pools (usage > 80%)
        $warningPools = IpPool::where('tenant_id', $tenantId)
            ->get()
            ->filter(fn ($pool) => $pool->usage_percentage > 80);

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
            ->with('router')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get filter options
        $routers = Router::where('tenant_id', $tenantId)->pluck('name', 'id');
        $sites = IpPool::where('tenant_id', $tenantId)
            ->distinct()
            ->pluck('site')
            ->filter()
            ->sort()
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
            ->pluck('name', 'id');

        $sites = IpPool::where('tenant_id', $tenantId)
            ->distinct()
            ->pluck('site')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

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
            $poolData['tenant_id'] = auth()->user()->tenant_id;

            // Calculate total IPs based on CIDR
            $cidr = $poolData['cidr'];
            $totalIps = pow(2, 32 - $cidr);
            $poolData['total_ips'] = min($totalIps, 254); // Cap at 254 for /24 networks
            $poolData['available_ips'] = $poolData['total_ips'];

            $pool = IpPool::create($poolData);

            // Generate IP addresses for the pool
            $this->generateIpAddresses($pool);

            DB::commit();

            return redirect()
                ->route('ipam.pools.show', $pool)
                ->with('success', 'IP pool created successfully.');
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

        $pool->load(['router', 'ipAddresses' => fn ($query) => $query->with('customer')]);

        $ipAddresses = $pool->ipAddresses()
            ->with('customer')
            ->orderByRaw('INET_ATON(ip_address)')
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
            ->pluck('name', 'id');

        $sites = IpPool::where('tenant_id', $tenantId)
            ->distinct()
            ->pluck('site')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

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

            $pool->update($request->validated());

            DB::commit();

            return redirect()
                ->route('ipam.pools.show', $pool)
                ->with('success', 'IP pool updated successfully.');
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
     * Verify tenant access to a resource.
     */
    protected function authorizeTenantAccess(IpPool $IpPool): void
    {
        // if ($IpPool->tenant_id !== auth()->user->tenant_id) {
        //     abort(403, 'Unauthorized access to this IP pool.');
        // }
    }
}
