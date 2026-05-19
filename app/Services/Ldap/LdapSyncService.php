<?php

namespace App\Services\Ldap;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LdapRecord\Models\Entry;
use LdapRecord\Models\Model as LdapModel;
use Throwable;

class LdapSyncService
{
    public function __construct(private readonly LdapConnectionFactory $connections) {}

    /**
     * @return array<string, mixed>
     */
    public static function defaultSettings(): array
    {
        return [
            'connection' => [
                'enabled' => false,
                'hosts' => [],
                'port' => 389,
                'base_dn' => null,
                'username' => null,
                'password' => null,
                'timeout' => 5,
                'use_tls' => false,
                'use_starttls' => false,
                'sync_interval_minutes' => 15,
                'missing_action' => 'mark_inactive',
            ],
            'customer_sync' => [
                'base_dn' => null,
                'filter' => '(objectClass=*)',
                'unique_attribute' => 'uid',
                'match_attribute' => 'uid',
                'map' => [
                    'name' => 'cn',
                    'email' => 'mail',
                    'phone' => 'telephoneNumber',
                    'mobile' => 'mobile',
                    'customer_code' => 'uid',
                    'status' => null,
                ],
            ],
            'subscription_sync' => [
                'base_dn' => null,
                'filter' => null,
                'unique_attribute' => 'uid',
                'customer_attribute' => 'uid',
                'customer_match_field' => 'customer_code',
                'map' => [
                    'subscription_code' => 'uid',
                    'pppoe_username' => 'uid',
                    'pppoe_password' => null,
                    'ip_address' => null,
                    'mac_address' => null,
                    'status' => null,
                ],
            ],
            'sync_status' => [
                'last_run_at' => null,
                'last_success_at' => null,
                'last_error' => null,
                'last_result' => null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsForTenant(string $tenantId): array
    {
        $defaults = self::defaultSettings();

        return [
            'connection' => array_replace_recursive($defaults['connection'], Setting::get('ldap.connection', [], $tenantId) ?? []),
            'customer_sync' => array_replace_recursive($defaults['customer_sync'], Setting::get('ldap.customer_sync', [], $tenantId) ?? []),
            'subscription_sync' => array_replace_recursive($defaults['subscription_sync'], Setting::get('ldap.subscription_sync', [], $tenantId) ?? []),
            'sync_status' => array_replace_recursive($defaults['sync_status'], Setting::get('ldap.sync_status', [], $tenantId) ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function syncTenant(Tenant $tenant, bool $dryRun = false): array
    {
        $settings = $this->settingsForTenant($tenant->id);

        if (! (bool) $settings['connection']['enabled']) {
            return $this->emptyResult('skipped');
        }

        if (! $dryRun) {
            $this->writeStatus($tenant->id, ['last_run_at' => now()->toISOString(), 'last_error' => null]);
        }

        try {
            $connection = $this->connections->register($tenant->id, $settings['connection']);
            $customers = $this->search($connection, $settings['customer_sync']);
            $subscriptions = filled($settings['subscription_sync']['base_dn']) && filled($settings['subscription_sync']['filter'])
                ? $this->search($connection, $settings['subscription_sync'])
                : collect();

            $result = $this->syncTenantFromEntries($tenant, $settings, $customers, $subscriptions, $dryRun);

            if (! $dryRun) {
                $this->writeStatus($tenant->id, [
                    'last_success_at' => now()->toISOString(),
                    'last_error' => null,
                    'last_result' => $result,
                ]);
            }

            return $result;
        } catch (Throwable $exception) {
            if (! $dryRun) {
                $this->writeStatus($tenant->id, ['last_error' => $exception->getMessage()]);
            }

            throw $exception;
        }
    }

    /**
     * @param  iterable<int, LdapModel|array<string, mixed>>  $customerEntries
     * @param  iterable<int, LdapModel|array<string, mixed>>  $subscriptionEntries
     * @return array<string, mixed>
     */
    public function syncTenantFromEntries(Tenant $tenant, array $settings, iterable $customerEntries, iterable $subscriptionEntries, bool $dryRun = false): array
    {
        $result = $this->emptyResult($dryRun ? 'preview' : 'synced');
        $syncedCustomerGuids = [];
        $syncedSubscriptionGuids = [];

        foreach ($customerEntries as $entry) {
            $guid = $this->guid($entry, $settings['customer_sync']['unique_attribute']);

            if (! filled($guid)) {
                $result['customers']['skipped']++;

                continue;
            }

            $customer = $this->findCustomer($tenant->id, $guid, $entry, $settings);
            $result['customers'][$customer?->exists ? 'updated' : 'created']++;
            $syncedCustomerGuids[] = $guid;

            if (! $dryRun) {
                $attributes = $this->customerAttributes($tenant->id, $entry, $settings['customer_sync'], $guid);
                $customer ??= new Customer;
                $this->restoreIfTrashed($customer);
                $customer->forceFill($attributes)->save();
            }
        }

        foreach ($subscriptionEntries as $entry) {
            $guid = $this->guid($entry, $settings['subscription_sync']['unique_attribute']);

            if (! filled($guid)) {
                $result['subscriptions']['skipped']++;

                continue;
            }

            $customer = $this->subscriptionCustomer($tenant->id, $entry, $settings);

            if (! $customer) {
                $result['subscriptions']['skipped']++;

                continue;
            }

            $subscription = $this->findSubscription($tenant->id, $guid, $entry, $settings);
            $result['subscriptions'][$subscription?->exists ? 'updated' : 'created']++;
            $syncedSubscriptionGuids[] = $guid;

            if (! $dryRun) {
                $attributes = $this->subscriptionAttributes($tenant->id, $customer, $entry, $settings['subscription_sync'], $guid);
                $subscription ??= new Subscription;
                $this->restoreIfTrashed($subscription);
                $subscription->forceFill($attributes)->save();
            }
        }

        if (! $dryRun) {
            $result['customers']['missing'] = $this->handleMissingCustomers($tenant->id, $syncedCustomerGuids, $settings['connection']['missing_action']);
            $result['subscriptions']['missing'] = $this->handleMissingSubscriptions($tenant->id, $syncedSubscriptionGuids, $settings['connection']['missing_action']);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $syncSettings
     * @return Collection<int, LdapModel>
     */
    private function search(string $connection, array $syncSettings): Collection
    {
        return Entry::on($connection)
            ->in($syncSettings['base_dn'])
            ->rawFilter($syncSettings['filter'])
            ->get($this->selects($syncSettings));
    }

    /**
     * @param  array<string, mixed>  $syncSettings
     * @return array<int, string>
     */
    private function selects(array $syncSettings): array
    {
        return collect($syncSettings['map'] ?? [])
            ->merge(Arr::only($syncSettings, ['unique_attribute', 'match_attribute', 'customer_attribute']))
            ->filter()
            ->push('dn')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     * @param  array<string, mixed>  $settings
     */
    private function findCustomer(string $tenantId, string $guid, LdapModel|array $entry, array $settings): ?Customer
    {
        $query = Customer::withoutGlobalScopes()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($guid, $entry, $settings): void {
                $query->where('ldap_guid', $guid);

                $code = $this->mapped($entry, $settings['customer_sync']['map']['customer_code'] ?? null);

                if (filled($code)) {
                    $query->orWhere('customer_code', $code);
                }
            });

        return $query->first();
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     * @param  array<string, mixed>  $settings
     */
    private function findSubscription(string $tenantId, string $guid, LdapModel|array $entry, array $settings): ?Subscription
    {
        $query = Subscription::withoutGlobalScopes()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($guid, $entry, $settings): void {
                $query->where('ldap_guid', $guid);

                $code = $this->mapped($entry, $settings['subscription_sync']['map']['subscription_code'] ?? null);

                if (filled($code)) {
                    $query->orWhere('subscription_code', $code);
                }
            });

        return $query->first();
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     * @param  array<string, mixed>  $settings
     */
    private function subscriptionCustomer(string $tenantId, LdapModel|array $entry, array $settings): ?Customer
    {
        $value = $this->mapped($entry, $settings['subscription_sync']['customer_attribute'] ?? null);
        $field = $settings['subscription_sync']['customer_match_field'] ?? 'customer_code';

        if (! filled($value)) {
            return null;
        }

        return Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where($field, $value)
            ->first();
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     * @param  array<string, mixed>  $syncSettings
     * @return array<string, mixed>
     */
    private function customerAttributes(string $tenantId, LdapModel|array $entry, array $syncSettings, string $guid): array
    {
        $name = $this->mapped($entry, $syncSettings['map']['name'] ?? null) ?: 'LDAP Customer '.$guid;
        [$firstName, $lastName] = $this->splitName($name);

        return [
            'tenant_id' => $tenantId,
            'ldap_guid' => $guid,
            'ldap_domain' => $this->domain($entry),
            'ldap_dn' => $this->dn($entry),
            'ldap_synced_at' => now(),
            'customer_type' => 'individual',
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'customer_code' => $this->mapped($entry, $syncSettings['map']['customer_code'] ?? null) ?: $guid,
            'email' => $this->mapped($entry, $syncSettings['map']['email'] ?? null),
            'phone' => $this->mapped($entry, $syncSettings['map']['phone'] ?? null),
            'mobile' => $this->mapped($entry, $syncSettings['map']['mobile'] ?? null),
            'status' => $this->status($this->mapped($entry, $syncSettings['map']['status'] ?? null), ['pending', 'active', 'inactive', 'suspended'], 'active'),
            'billing_type' => 'prepaid',
            'billing_enabled' => true,
        ];
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     * @param  array<string, mixed>  $syncSettings
     * @return array<string, mixed>
     */
    private function subscriptionAttributes(string $tenantId, Customer $customer, LdapModel|array $entry, array $syncSettings, string $guid): array
    {
        return [
            'tenant_id' => $tenantId,
            'customer_id' => $customer->id,
            'ldap_guid' => $guid,
            'ldap_domain' => $this->domain($entry),
            'ldap_dn' => $this->dn($entry),
            'ldap_synced_at' => now(),
            'subscription_code' => $this->mapped($entry, $syncSettings['map']['subscription_code'] ?? null) ?: $guid,
            'connection_type' => 'pppoe',
            'pppoe_username' => $this->mapped($entry, $syncSettings['map']['pppoe_username'] ?? null),
            'pppoe_password' => $this->mapped($entry, $syncSettings['map']['pppoe_password'] ?? null),
            'ip_address' => $this->mapped($entry, $syncSettings['map']['ip_address'] ?? null),
            'mac_address' => $this->mapped($entry, $syncSettings['map']['mac_address'] ?? null),
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => $this->status($this->mapped($entry, $syncSettings['map']['status'] ?? null), ['pending', 'active', 'suspended', 'cancelled'], 'active'),
            'start_date' => now(),
            'activation_date' => now(),
        ];
    }

    /**
     * @param  array<int, string>  $syncedGuids
     */
    private function handleMissingCustomers(string $tenantId, array $syncedGuids, string $action): int
    {
        if ($action === 'ignore') {
            return 0;
        }

        $query = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('ldap_guid')
            ->when($syncedGuids !== [], fn ($query) => $query->whereNotIn('ldap_guid', $syncedGuids));

        if ($action === 'soft_delete') {
            $customers = $query->get();
            $customers->each->delete();

            return $customers->count();
        }

        return $query->update(['status' => 'inactive']);
    }

    /**
     * @param  array<int, string>  $syncedGuids
     */
    private function handleMissingSubscriptions(string $tenantId, array $syncedGuids, string $action): int
    {
        if ($action === 'ignore') {
            return 0;
        }

        $query = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('ldap_guid')
            ->when($syncedGuids !== [], fn ($query) => $query->whereNotIn('ldap_guid', $syncedGuids));

        if ($action === 'soft_delete') {
            $subscriptions = $query->get();
            $subscriptions->each->delete();

            return $subscriptions->count();
        }

        return $query->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     */
    private function guid(LdapModel|array $entry, ?string $attribute): ?string
    {
        $value = $this->mapped($entry, $attribute);

        if (! filled($value)) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     */
    private function mapped(LdapModel|array $entry, ?string $attribute): ?string
    {
        if (! filled($attribute)) {
            return null;
        }

        if ($entry instanceof LdapModel) {
            $value = $entry->getFirstAttribute($attribute);

            return filled($value) ? (string) $value : null;
        }

        $attributes = array_change_key_case($entry, CASE_LOWER);
        $value = $attributes[strtolower($attribute)] ?? null;
        $value = is_array($value) ? Arr::first($value) : $value;

        return filled($value) ? (string) $value : null;
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     */
    private function dn(LdapModel|array $entry): ?string
    {
        if ($entry instanceof LdapModel) {
            return $entry->getDn();
        }

        return $entry['dn'] ?? $entry['distinguishedname'] ?? null;
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     */
    private function domain(LdapModel|array $entry): string
    {
        return $entry instanceof LdapModel ? ($entry->getConnectionName() ?? 'default') : 'default';
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function status(?string $value, array $allowed, string $default): string
    {
        $status = strtolower((string) $value);

        return in_array($status, $allowed, true) ? $status : $default;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? $name, $parts[1] ?? null];
    }

    private function restoreIfTrashed(Customer|Subscription $model): void
    {
        if (method_exists($model, 'trashed') && $model->trashed()) {
            $model->restore();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(string $status): array
    {
        return [
            'status' => $status,
            'customers' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'missing' => 0],
            'subscriptions' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'missing' => 0],
        ];
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function writeStatus(string $tenantId, array $status): void
    {
        $current = Setting::get('ldap.sync_status', [], $tenantId) ?? [];

        Setting::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => 'ldap.sync_status'],
            ['value' => array_replace($current, $status), 'type' => 'json', 'group' => 'ldap']
        );
    }
}
