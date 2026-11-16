<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\GolestanStudentLink;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Scopes\UserGroupScope;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GolestanSyncService
{
    public const DEFAULT_CREDENTIAL_FIELDS = ['username' => 'STDNO', 'password' => 'SSNO'];

    public function credentialFieldOptions(array $records): array
    {
        $labels = [
            'STDNO' => 'شماره دانشجویی (STDNO)',
            'SSNO' => 'شماره ملی (SSNO)',
            'firstname' => 'نام (firstname)',
            'name' => 'نام خانوادگی (name)',
        ];
        $options = array_intersect_key($labels, array_flip(self::DEFAULT_CREDENTIAL_FIELDS));
        foreach ($records as $record) {
            foreach (array_keys($record) as $field) {
                $options[$field] = $labels[$field] ?? $field;
            }
        }

        return $options;
    }

    public function __construct(private OrganizationBillingService $billing, private SubscriptionUsageService $usage) {}

    public function remember(string $type, array $data): string
    {
        $token = (string) Str::uuid();
        Cache::put("golestan:{$type}:{$token}", Crypt::encryptString(json_encode($data, JSON_THROW_ON_ERROR)), now()->addMinutes(20));

        return $token;
    }

    public function recall(string $type, string $token, string $tenantId, int $userId): array
    {
        $encrypted = Cache::get("golestan:{$type}:{$token}");
        $this->require(is_string($encrypted), 'This search or preview has expired. Search again to continue.');
        $data = json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
        abort_unless($data['tenant_id'] === $tenantId && $data['user_id'] === $userId, 403);

        return $data;
    }

    public function normalize(array $record): array
    {
        return [
            'student_number' => $this->digits($record['STDNO'] ?? ''),
            'national_id' => $this->digits($record['SSNO'] ?? ''),
            'first_name' => trim($record['firstname'] ?? ''),
            'last_name' => trim($record['name'] ?? ''),
            'fields' => $record,
        ];
    }

    public function settings(string $tenantId, array $input): array
    {
        $plan = Plan::query()->where('tenant_id', $tenantId)->active()->find($input['plan_id']);
        $router = Router::query()->where('tenant_id', $tenantId)->find($input['router_id']);
        $this->require($plan !== null && $router !== null, 'Choose an available plan and router.');
        $pool = null;
        if ($input['ip_management'] === 'system') {
            $pool = IpPool::query()->where('tenant_id', $tenantId)->whereIn('status', ['active', 'exhausted'])->find($input['ip_pool_id']);
            $this->require($pool !== null && ($pool->all_devices || (int) $pool->router_id === $router->id
                || $pool->routers()->where('routers.tenant_id', $tenantId)->whereKey($router->id)->exists()),
                'Choose an active IP pool available on the selected router.');
        }

        return [$plan, $router, $pool, $input['credential_fields'] ?? null];
    }

    public function inspect(string $tenantId, array $student, array $settings, ?int $choice, ?string $address, bool $lock = false): array
    {
        [$plan, $router, $pool, $credentialFields] = $settings;
        $number = $student['student_number'];
        $this->require(preg_match('/^[0-9]{1,64}$/D', $number) === 1, 'The student number is missing or invalid.');
        $nationalId = $student['national_id'];
        $this->require($nationalId === '' || preg_match('/^[0-9]{10}$/D', $nationalId) === 1, 'The national ID is invalid.');
        $link = GolestanStudentLink::query()->where('tenant_id', $tenantId)->where('student_number', $number)
            ->when($lock, fn ($query) => $query->lockForUpdate())->first();
        $customerQuery = Customer::query()->where('tenant_id', $tenantId);
        $matches = $nationalId === '' ? collect() : (clone $customerQuery)->withoutGlobalScope(UserGroupScope::class)->withTrashed()
            ->whereIn('national_id', [
                $nationalId,
                strtr($nationalId, array_combine(str_split('0123456789'), mb_str_split('۰۱۲۳۴۵۶۷۸۹'))),
                strtr($nationalId, array_combine(str_split('0123456789'), mb_str_split('٠١٢٣٤٥٦٧٨٩'))),
            ])->limit(2)->when($lock, fn ($query) => $query->lockForUpdate())->get();
        $matchedId = $matches->first()?->id;
        $this->require(! $matchedId || (clone $customerQuery)->whereKey($matchedId)->exists(), 'The matching customer is unavailable for sync.');
        $this->require($matches->count() <= 1, 'More than one customer has this national ID.');
        $customer = $link ? $customerQuery->when($lock, fn ($query) => $query->lockForUpdate())->find($link->customer_id) : $matches->first();
        $storedNationalId = $this->digits($customer?->national_id ?? '');
        $this->require(! $link || ($customer && ($matches->isEmpty() || $matches->first()->is($customer))
            && ($nationalId === '' || $storedNationalId === '' || $storedNationalId === $nationalId)), 'The linked customer has conflicting identity information or is unavailable.');
        $this->require(! $customer || $customer->customer_type === 'individual', 'The matching customer is a business.');
        $names = array_filter(array_intersect_key($student, array_flip(['first_name', 'last_name'])), fn (string $value) => $value !== '');
        $this->require($customer !== null || count($names) === 2, 'A new customer needs both a first name and a surname.');
        $this->require(collect($names)->every(fn (string $name) => mb_strlen($name) <= 255)
            && mb_strlen(trim(($names['first_name'] ?? $customer?->first_name).' '.($names['last_name'] ?? $customer?->last_name))) <= 255,
            'The student name is too long.');
        if ($customer) {
            $this->billing->assertPlanAllowedForCustomer($customer->id, $plan->id);
        }

        $subscriptionQuery = Subscription::query()->where('tenant_id', $tenantId)->where('customer_id', $customer?->id);
        $candidates = $customer && ! $link?->subscription_id
            ? (clone $subscriptionQuery)->where('status', '!=', 'cancelled')
                ->whereNotIn('id', GolestanStudentLink::query()->where('tenant_id', $tenantId)->whereNotNull('subscription_id')->select('subscription_id'))
                ->orderBy('name')->get(['id', 'name', 'subscription_code'])
            : collect();
        if ($candidates->isNotEmpty() && $choice === null) {
            return ['preview' => ['action' => 'choose', 'candidates' => $candidates->toArray(), 'message' => 'Choose an existing subscription or create a new one.']];
        }
        $target = $link?->subscription_id ?: $choice;
        $this->require($link?->subscription_id || ! $choice || $candidates->contains('id', $choice), 'The selected subscription is unavailable.');
        $subscription = $target ? $subscriptionQuery->when($lock, fn ($query) => $query->lockForUpdate())->find($target) : null;
        $this->require(! $target || ($subscription && $subscription->status !== 'cancelled'), 'The linked subscription is cancelled or unavailable.');

        $credentials = [];
        $credentialState = [];
        $username = null;
        if ($credentialFields !== null) {
            $this->require(! $subscription || $subscription->isPppoe() || blank($subscription->connection_type),
                'The selected subscription uses a different connection type. Choose a PPPoE subscription or create a new one.');
            $username = $this->credentialValue($student, $credentialFields['username']);
            $password = $this->credentialValue($student, $credentialFields['password']);
            $this->require(Validator::make(['username' => $username], [
                'username' => ['required', 'string', 'max:255', SafeRouterUsername::validationRule()],
            ])->passes(), 'The mapped PPPoE username is missing or invalid.');
            $this->require(filled($password) && mb_strlen($password) <= 255 && ! preg_match('/[\x00-\x1f\x7f]/', $password),
                'The mapped PPPoE password is missing or invalid.');
            $usernameInUse = Subscription::query()->withoutGlobalScope(UserGroupScope::class)
                ->where('tenant_id', $tenantId)->whereRaw('LOWER(pppoe_username) = ?', [mb_strtolower($username)])
                ->when($subscription, fn ($query) => $query->whereKeyNot($subscription->id))->exists();
            $this->require(! $usernameInUse, 'The mapped PPPoE username is already used by another subscription.');
            if ($username !== $subscription?->pppoe_username) {
                $this->require($subscription?->connection_status !== 'online' && ! $subscription?->ipLeases()->whereNull('released_at')->exists(),
                    'Disconnect the active session before changing its PPPoE username.');
                $credentials['pppoe_username'] = $username;
            }
            if ($password !== $subscription?->pppoe_password) {
                $credentials['pppoe_password'] = $password;
            }
            if ($subscription?->connection_type !== 'pppoe') {
                $credentials['connection_type'] = 'pppoe';
            }
            $credentialState = [$subscription?->connection_type, $subscription?->pppoe_username, $subscription?->getRawOriginal('pppoe_password')];
        }

        $network = ['router_id' => $router->id, 'ip_pool_id' => $pool?->id, 'ip_management' => $pool ? 'system' : 'router'];
        $networkChanged = $subscription && collect($network)->contains(fn ($value, $key) => $value != $subscription->$key);
        $ipChanged = $address !== null && $address !== $subscription?->ip_address;
        $this->require(! ($networkChanged || $ipChanged) || $subscription?->connection_status !== 'online',
            'Disconnect the active session before changing this subscription’s router or IP settings.');
        $this->require(! ($networkChanged || $ipChanged) || ! $subscription
            || (! $subscription->usesSessionIpAllocation() && ! $subscription->ipRoutes()->exists()),
            'Change the network settings for subscriptions with session allocation or routed IPs in the subscription editor.');
        $allocate = $pool && ! $subscription?->usesSessionIpAllocation()
            && ($networkChanged || $ipChanged || blank($subscription?->ip_address));
        if ($address !== null && $pool) {
            $ip = IpAddress::query()->where('tenant_id', $tenantId)->where('ip_pool_id', $pool->id)->where('ip_address', $address)->first();
            $this->require($ip && ($ip->isAvailable() || ($subscription && $ip->subscription_code === $subscription->subscription_code)),
                'The requested IP address is unavailable in the selected pool.');
        }
        $current = $subscription?->only(['plan_id', 'router_id', 'ip_pool_id', 'ip_management', 'ip_address', 'status']);
        $proposedIp = $allocate ? ($address ?? 'Automatic') : $subscription?->ip_address;
        if (! $pool && $networkChanged) {
            $proposedIp = null;
        }
        $proposed = ['plan_id' => $plan->id, ...$network, 'ip_address' => $proposedIp];
        $customerFields = $names;
        if ($names !== []) {
            $customerFields['name'] = trim(($names['first_name'] ?? $customer?->first_name).' '.($names['last_name'] ?? $customer?->last_name));
        }
        if ($nationalId !== '' && $storedNationalId === '') {
            $customerFields['national_id'] = $nationalId;
        }
        $fingerprint = hash('sha256', json_encode([
            $link?->id, $customer?->id, $customer?->only(['first_name', 'last_name', 'national_id']), $subscription?->id, $current, $proposed,
            ...$credentialState,
        ], JSON_THROW_ON_ERROR));

        return [
            'preview' => [
                'action' => 'ready',
                'customer_action' => match (true) {
                    $customer === null => 'Create customer',
                    (clone $customer)->fill($customerFields)->isDirty() => 'Update customer',
                    default => 'Reuse customer',
                },
                'subscription_action' => match (true) {
                    $subscription === null => 'Create pending subscription',
                    $networkChanged || $ipChanged || $allocate || $credentials !== [] || $subscription->plan_id != $plan->id => 'Update subscription',
                    ! $link?->subscription_id => 'Link existing subscription',
                    default => 'Reuse subscription',
                },
                'candidates' => $candidates->toArray(),
                'current' => $current,
                'proposed' => $proposed,
                'needs_ip' => (bool) $allocate,
                'credentials_changed' => $credentials !== [],
                'session_notice' => $subscription?->status === 'active' && ($networkChanged || $ipChanged || $subscription->plan_id != $plan->id),
            ],
            'fingerprint' => $fingerprint, 'customer' => $customer, 'customer_fields' => $customerFields, 'subscription' => $subscription,
            'link' => $link, 'network' => $network, 'credentials' => $credentials, 'username' => $username, 'allocate' => $allocate, 'network_changed' => $networkChanged || $ipChanged,
        ];
    }

    public function apply(string $tenantId, array $row, array $settings): array
    {
        [$plan, , $pool] = $settings;
        $data = $this->inspect($tenantId, $row['student'], $settings, $row['choice'], $row['address'], lock: true);
        $this->require(($data['fingerprint'] ?? null) === $row['fingerprint'], 'The customer or subscription changed since preview. Preview this student again.');
        $customer = $data['customer'] ?? new Customer([
            'tenant_id' => $tenantId, 'customer_type' => 'individual', 'status' => 'active', 'billing_enabled' => false, 'billing_disabled_at' => now(),
        ]);
        $customer->fill($data['customer_fields']);
        $customerAction = ! $customer->exists ? 'Created' : ($customer->isDirty() ? 'Updated' : 'Unchanged');
        if ($customer->isDirty()) {
            $customer->save();
        }

        $subscription = $data['subscription'] ?? new Subscription([
            'tenant_id' => $tenantId, 'customer_id' => $customer->id, 'service_type' => 'hotspot',
            'status' => 'pending', 'billing_enabled' => false, 'billing_disabled_at' => now(),
            'auto_suspension_enabled' => false, 'ip_allocation' => 'fixed',
        ]);
        $creating = ! $subscription->exists;
        $planChanged = $subscription->plan_id != $plan->id;
        if ($data['network_changed'] && $subscription->isSystemManagedIp() && filled($subscription->ip_address)) {
            $subscription->releaseIpAddress();
        }
        $subscription->unsetRelations();
        $subscription->fill(['plan_id' => $plan->id, ...$data['network'], ...$data['credentials']]);
        if ($data['network_changed'] && ! $pool) {
            $subscription->ip_address = null;
        }
        if ($creating || $planChanged) {
            $subscription->fill(['billing_cycle' => $plan->billing_cycle, 'grace_period_days' => $plan->grace_period_days]);
        }
        if ($creating) {
            $subscription->base_price = $plan->price;
        }
        $subscriptionAction = $creating ? 'Created' : ($subscription->isDirty() || $data['allocate'] ? 'Updated' : ($data['link'] ? 'Unchanged' : 'Linked'));
        if ($subscription->isDirty()) {
            $subscription->save();
        }
        if ($data['allocate']) {
            $subscription->unsetRelation('ipPool');
            $this->require($subscription->assignIpAddress($row['address']) !== null, 'No suitable IP address is available.');
        }
        if (! $creating && $planChanged) {
            if ($customer->organization?->billing_enabled) {
                $this->billing->applyDefaultsToExistingSubscription($subscription, $customer->organization);
            }
            $cycle = $subscription->usageCycles()->whereNull('closed_at')->first();
            if ($cycle) {
                $cycle->update(['plan_id' => $plan->id, 'allowance_bytes' => $plan->dataLimitBytes()]);
                $this->usage->reconcile($subscription->fresh(['plan']));
            }
        }
        $link = $data['link'] ?? new GolestanStudentLink(['tenant_id' => $tenantId, 'student_number' => $row['student']['student_number']]);
        $link->fill(['customer_id' => $customer->id, 'subscription_id' => $subscription->id]);
        if ($link->isDirty()) {
            $link->save();
        }

        return [
            'status' => 'synced', 'message' => "{$customerAction} customer. {$subscriptionAction} subscription.",
            'customer_id' => $customer->id, 'subscription_id' => $subscription->id,
        ];
    }

    public function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['sync' => $message]);
        }
    }

    private function credentialValue(array $student, string $field): string
    {
        return match ($field) {
            'STDNO' => $student['student_number'],
            'SSNO' => $student['national_id'],
            default => trim($student['fields'][$field] ?? ''),
        };
    }

    private function digits(string $value): string
    {
        return strtr(trim($value), array_combine(mb_str_split('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩'), str_split('01234567890123456789')));
    }
}
