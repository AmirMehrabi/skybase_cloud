<?php

namespace App\Http\Controllers;

use App\Http\Requests\VpnUser\StoreVpnUserRequest;
use App\Http\Requests\VpnUser\UpdateVpnUserRequest;
use App\Models\UserGroup;
use App\Models\VpnUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class VpnUserController extends Controller
{
    public function index(Request $request): View
    {
        return view('vpn-users.index', [
            'onboarding' => $this->onboardingSettings($request),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'active', 'online']);

        $vpnUsers = VpnUser::query()
            ->filter($filters)
            ->latest('created_at')
            ->paginate($request->input('per_page', 15))
            ->through(fn (VpnUser $vpnUser): array => [
                'id' => $vpnUser->id,
                'username' => $vpnUser->username,
                'active' => $vpnUser->active,
                'online' => $vpnUser->online,
                'vpn_ip' => $vpnUser->vpn_ip,
                'real_ip' => $vpnUser->real_ip,
                'bytes_received' => $vpnUser->bytes_received,
                'bytes_sent' => $vpnUser->bytes_sent,
                'last_login_at' => $vpnUser->last_login_at?->format('M d, Y H:i'),
                'last_seen_at' => $this->lastSeenAt($vpnUser),
                'connected_at' => $vpnUser->connected_at?->format('M d, Y H:i'),
                'disconnected_at' => $vpnUser->disconnected_at?->format('M d, Y H:i'),
                'created_at' => $vpnUser->created_at?->format('M d, Y'),
                'show_url' => route('vpn-users.show', $vpnUser),
                'edit_url' => route('vpn-users.edit', $vpnUser),
            ]);

        return response()->json([
            'vpnUsers' => $vpnUsers->items(),
            'pagination' => [
                'current_page' => $vpnUsers->currentPage(),
                'last_page' => $vpnUsers->lastPage(),
                'per_page' => $vpnUsers->perPage(),
                'total' => $vpnUsers->total(),
                'from' => $vpnUsers->firstItem(),
                'to' => $vpnUsers->lastItem(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(VpnUser::getStats());
    }

    public function create(): View
    {
        return view('vpn-users.create', ['userGroups' => UserGroup::query()->orderBy('name')->pluck('name', 'id')]);
    }

    public function store(StoreVpnUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        VpnUser::create([
            'tenant_id' => (string) (tenant()?->id ?? $request->user()->tenant_id),
            'username' => $validated['username'],
            'password_hash' => Hash::make($validated['password']),
            'active' => $request->boolean('active'),
            'user_group_id' => $request->user()->isOwner()
                ? ($validated['user_group_id'] ?? null)
                : $request->user()->user_group_id,
        ]);

        return redirect()
            ->route('vpn-users.index')
            ->with('success', 'VPN user created successfully.');
    }

    public function show(VpnUser $vpnUser): View
    {
        $this->authorizeTenantAccess($vpnUser);

        return view('vpn-users.show', compact('vpnUser'));
    }

    public function edit(VpnUser $vpnUser): View
    {
        $this->authorizeTenantAccess($vpnUser);

        return view('vpn-users.edit', [
            'vpnUser' => $vpnUser,
            'userGroups' => UserGroup::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdateVpnUserRequest $request, VpnUser $vpnUser): RedirectResponse
    {
        $this->authorizeTenantAccess($vpnUser);

        $validated = $request->validated();
        $updates = [
            'username' => $validated['username'],
            'active' => $request->boolean('active'),
            'user_group_id' => $request->user()->isOwner()
                ? ($validated['user_group_id'] ?? null)
                : $vpnUser->user_group_id,
        ];

        if (! empty($validated['password'])) {
            $updates['password_hash'] = Hash::make($validated['password']);
        }

        $vpnUser->update($updates);

        return redirect()
            ->route('vpn-users.show', $vpnUser)
            ->with('success', 'VPN user updated successfully.');
    }

    public function destroy(VpnUser $vpnUser): RedirectResponse
    {
        $this->authorizeTenantAccess($vpnUser);

        $vpnUser->delete();

        return redirect()
            ->route('vpn-users.index')
            ->with('success', 'VPN user deleted successfully.');
    }

    protected function authorizeTenantAccess(VpnUser $vpnUser): void
    {
        if (auth()->check() && auth()->user()->tenant_id && $vpnUser->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'You do not have access to this VPN user.');
        }
    }

    private function lastSeenAt(VpnUser $vpnUser): ?string
    {
        return ($vpnUser->online ? $vpnUser->connected_at : $vpnUser->disconnected_at)
            ?->format('M d, Y H:i')
            ?? $vpnUser->last_login_at?->format('M d, Y H:i');
    }

    /**
     * @return array{remote_host: string, remote_port: int, protocol: string, config: string}
     */
    private function onboardingSettings(Request $request): array
    {
        $remoteHost = $this->openVpnRemoteHost($request);
        $remotePort = 1194;
        $protocol = 'udp';

        return [
            'remote_host' => $remoteHost,
            'remote_port' => $remotePort,
            'protocol' => $protocol,
            'config' => $this->clientConfig($remoteHost, $remotePort, $protocol),
        ];
    }

    private function openVpnRemoteHost(Request $request): string
    {
        $host = $request->getHost();

        if (! in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            return $host;
        }

        return $request->server('SERVER_ADDR') ?: $host;
    }

    private function clientConfig(string $remoteHost, int $remotePort, string $protocol): string
    {
        return trim(<<<OVPN
client
dev tun
proto {$protocol}
remote {$remoteHost} {$remotePort}
resolv-retry infinite
nobind
persist-key
persist-tun

remote-cert-tls server
auth SHA256
data-ciphers AES-256-GCM:AES-128-GCM:CHACHA20-POLY1305

auth-user-pass
auth-nocache

verb 3

<ca>
-----BEGIN CERTIFICATE-----
MIIDSzCCAjOgAwIBAgIUKH0jJ04wcJMP9OIXbD+ePMzb7DswDQYJKoZIhvcNAQEL
BQAwFjEUMBIGA1UEAwwLRWFzeS1SU0EgQ0EwHhcNMjYwNTA4MDczNDE1WhcNMzYw
NTA1MDczNDE1WjAWMRQwEgYDVQQDDAtFYXN5LVJTQSBDQTCCASIwDQYJKoZIhvcN
AQEBBQADggEPADCCAQoCggEBAMRLxBBTemQGNcjsH7kuNTV4BhMSMik9CwfPMcsK
JdeZKu/YvFjPbdKPwVw1WGpFoEyb3Zb01rPcNwvJh4gkW8SV4Fe0RpFn8CsSbNeQ
Uca6KGfIElcWUekYGDBfICrwKENb6k+rUA4AvlLwKjosqptU8hwp4L3Z+/f7XC6O
dWkKc3uFqLMNUVU8KO9/Yc/PfUqLc/G6RaWOEkHbdk7wlwms5rkwyiL+iDnFQ65F
d0WK6xKVYRBSyZYRGqB1qwin6bkNGLDRL/zXedTzjrG0jqKcQb3Rpy181+LIK20N
eb7MRKDhnoEGKxHXkbQrjX0fwfzLnlWqxbKrl5nzmRkcHX0CAwEAAaOBkDCBjTAM
BgNVHRMEBTADAQH/MB0GA1UdDgQWBBRvUgMtsRtQb08oTLzuMIJVoLHoIDBRBgNV
HSMESjBIgBRvUgMtsRtQb08oTLzuMIJVoLHoIKEapBgwFjEUMBIGA1UEAwwLRWFz
eS1SU0EgQ0GCFCh9IydOMHCTD/TiF2w/njzM2+w7MAsGA1UdDwQEAwIBBjANBgkq
hkiG9w0BAQsFAAOCAQEAokOGXKPM0b9MyhShwrnR79/iA08cxTJgkMMe03+itYqr
nijuhs+Z4tLL1rAJdFfAXRmLUistyR8qqlv2S68PKyY8x67VcgWbxPOJ8f11Gu1O
brWDC7P0gFtpCGauIWmAX55iKB0QAZcwSznGiF8BGg9kft66y26fo/dtIe7DPQht
3J5yzWw2Wncuzzt56+ujtO7uCc3m/M4tR3d6XUjrZ+yfJTCh6nCU+mBmMRItJo5u
5RC6o6cbx16jq9Kccf9Q7nO3Yrw1qfcYHjbEBoih+PSHlbHHI0ZkYcLJrU/YRf7l
IB8GTYqeIgfIKrs8hXBbJRdf485MqBWrCEuvMJiKfg==
-----END CERTIFICATE-----
</ca>

<tls-crypt>
#
# 2048 bit OpenVPN static key
#
-----BEGIN OpenVPN Static key V1-----
0a3197e605e9eef81a793fa1e1dc8d5b
6e19770e1a93926722b71d022f11a22a
5d7e65cd0e4c7ee893a04eb34ad373a7
7df40f0ddb11062f221865439b22b656
a238ee7ef8d68ceb8737648b3c3f65d5
83d0dae969097b51c4003415bb69ef6f
323679fda419a0b1dbc1a87fcceec005
2b3c3ec16901f606cd860ca7189d1bd3
a5053f999b3626eb6171f5477be41d12
ca86efe5db57234a3ab0598f857b6d0f
fe1a7cd7c8454fabca184364cc655813
c8458c0eb17e1a2c1a77b5c4d0752e2c
1f0e7d38957b5cc1c8671106d2e02d0e
7776ce9608c62319bcea2d409e1f7656
baef96149e25ca2e480d29b98d3fc4a0
2067d95cb0b4791b738a7e5f877724b6
-----END OpenVPN Static key V1-----
</tls-crypt>
OVPN);
    }
}
