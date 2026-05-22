<?php

namespace App\Services\Ldap;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
            'organization_sync' => [
                'base_dn' => null,
                'filter' => null,
                'unique_attribute' => 'objectGUID',
                'match_attribute' => 'objectGUID',
                'excluded_ou_dns' => [],
                'map' => [
                    'code' => 'ou',
                    'name' => 'ou',
                    'description' => 'description',
                    'status' => null,
                ],
            ],
            'customer_sync' => [
                'base_dn' => null,
                'filter' => '(objectClass=*)',
                'unique_attribute' => 'uid',
                'match_attribute' => 'uid',
                'organization_attribute' => null,
                'organization_match_field' => 'code',
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
            'organization_sync' => array_replace_recursive($defaults['organization_sync'], Setting::get('ldap.organization_sync', [], $tenantId) ?? []),
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
            Log::info('LDAP sync search started.', [
                'tenant_id' => $tenant->id,
                'dry_run' => $dryRun,
                'base_dn' => $settings['connection']['base_dn'] ?? null,
                'excluded_ou_count' => count($settings['organization_sync']['excluded_ou_dns'] ?? []),
                'customer_base_dn' => $settings['customer_sync']['base_dn'] ?? null,
                'subscription_base_dn' => $settings['subscription_sync']['base_dn'] ?? null,
            ]);

            $discoveredOus = $this->discoverOrganizationalUnits($connection, $settings);
            $selectedOus = $this->selectedOrganizationalUnits($discoveredOus);
            $useOuScopes = $discoveredOus->isNotEmpty();
            $selectedOuDns = $selectedOus->pluck('dn')->filter()->values()->all();
            $organizations = $selectedOus->map(fn (array $ou): array => $ou['entry']);
            $customers = $useOuScopes
                ? $this->searchAcrossBaseDns($connection, $settings['customer_sync'], $selectedOuDns)
                : $this->search($connection, $settings['customer_sync']);
            $subscriptions = filled($settings['subscription_sync']['filter'])
                ? ($useOuScopes
                    ? $this->searchAcrossBaseDns($connection, $settings['subscription_sync'], $selectedOuDns)
                    : (filled($settings['subscription_sync']['base_dn']) ? $this->search($connection, $settings['subscription_sync']) : collect()))
                : collect();

            Log::info('LDAP sync search completed.', [
                'tenant_id' => $tenant->id,
                'dry_run' => $dryRun,
                'organizational_units_found' => $discoveredOus->count(),
                'organizational_units_selected' => $selectedOus->count(),
                'organizations_found' => $organizations->count(),
                'customers_found' => $customers->count(),
                'subscriptions_found' => $subscriptions->count(),
            ]);

            $result = $this->syncTenantFromEntries($tenant, $settings, $customers, $subscriptions, $dryRun, $organizations, $useOuScopes ? $selectedOuDns : null);

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
    public function syncTenantFromEntries(Tenant $tenant, array $settings, iterable $customerEntries, iterable $subscriptionEntries, bool $dryRun = false, iterable $organizationEntries = [], ?array $selectedOuDns = null): array
    {
        $result = $this->emptyResult($dryRun ? 'preview' : 'synced');
        $syncedOrganizationGuids = [];
        $syncedCustomerGuids = [];
        $syncedSubscriptionGuids = [];

        foreach ($organizationEntries as $entry) {
            $guid = $this->guid($entry, $settings['organization_sync']['unique_attribute']);

            if (! filled($guid)) {
                $result['organizations']['skipped']++;
                $this->logSkippedEntry($tenant->id, 'organization', 'missing_unique_attribute', $entry, [
                    'unique_attribute' => $settings['organization_sync']['unique_attribute'] ?? null,
                    'available_attributes' => $this->attributeNames($entry),
                ]);

                continue;
            }

            $organization = $this->findOrganization($tenant->id, $guid, $entry, $settings);
            $result['organizations'][$organization?->exists ? 'updated' : 'created']++;
            $syncedOrganizationGuids[] = $guid;

            Log::info('LDAP organization sync prepared.', [
                'tenant_id' => $tenant->id,
                'dry_run' => $dryRun,
                'action' => $organization?->exists ? 'updated' : 'created',
                'ldap_guid' => $guid,
                'dn' => $this->dn($entry),
                'mapped_code' => $this->mapped($entry, $settings['organization_sync']['map']['code'] ?? null),
                'mapped_name' => $this->mapped($entry, $settings['organization_sync']['map']['name'] ?? null),
            ]);

            if (! $dryRun) {
                $attributes = $this->organizationAttributes($tenant->id, $entry, $settings['organization_sync'], $guid);
                $organization ??= new Organization;
                $this->restoreIfTrashed($organization);
                $organization->forceFill($attributes)->save();
                Log::info('LDAP organization saved.', [
                    'tenant_id' => $tenant->id,
                    'organization_id' => $organization->id,
                    'code' => $organization->code,
                    'ldap_guid' => $guid,
                ]);
            }
        }

        foreach ($customerEntries as $entry) {
            $guid = $this->guid($entry, $settings['customer_sync']['unique_attribute']);

            if (! filled($guid)) {
                $result['customers']['skipped']++;
                $this->logSkippedEntry($tenant->id, 'customer', 'missing_unique_attribute', $entry, [
                    'unique_attribute' => $settings['customer_sync']['unique_attribute'] ?? null,
                    'available_attributes' => $this->attributeNames($entry),
                ]);

                continue;
            }

            $customer = $this->findCustomer($tenant->id, $guid, $entry, $settings);
            $result['customers'][$customer?->exists ? 'updated' : 'created']++;
            $syncedCustomerGuids[] = $guid;

            if (! $dryRun) {
                $attributes = $this->customerAttributes($tenant->id, $entry, $settings['customer_sync'], $guid);
                $customer ??= new Customer;
                $this->restoreIfTrashed($customer);
                Log::info('LDAP customer save starting.', [
                    'tenant_id' => $tenant->id,
                    'existing_customer_id' => $customer->exists ? $customer->id : null,
                    'ldap_guid' => $guid,
                    'customer_code' => $attributes['customer_code'] ?? null,
                    'name' => $attributes['name'] ?? null,
                    'email' => $attributes['email'] ?? null,
                    'organization_id' => $attributes['organization_id'] ?? null,
                    'status' => $attributes['status'] ?? null,
                    'dn' => $this->dn($entry),
                ]);
                $customer->forceFill($attributes)->save();
                Log::info('LDAP customer saved.', [
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'organization_id' => $customer->organization_id,
                    'ldap_guid' => $guid,
                ]);
            }
        }

        foreach ($subscriptionEntries as $entry) {
            $guid = $this->guid($entry, $settings['subscription_sync']['unique_attribute']);

            if (! filled($guid)) {
                $result['subscriptions']['skipped']++;
                $this->logSkippedEntry($tenant->id, 'subscription', 'missing_unique_attribute', $entry, [
                    'unique_attribute' => $settings['subscription_sync']['unique_attribute'] ?? null,
                    'available_attributes' => $this->attributeNames($entry),
                ]);

                continue;
            }

            $customer = $this->subscriptionCustomer($tenant->id, $entry, $settings);

            if (! $customer) {
                $result['subscriptions']['skipped']++;
                $this->logSkippedEntry($tenant->id, 'subscription', 'customer_not_found', $entry, [
                    'customer_attribute' => $settings['subscription_sync']['customer_attribute'] ?? null,
                    'customer_attribute_value' => $this->mapped($entry, $settings['subscription_sync']['customer_attribute'] ?? null),
                    'customer_match_field' => $settings['subscription_sync']['customer_match_field'] ?? null,
                    'subscription_guid' => $guid,
                ]);

                continue;
            }

            $subscription = $this->findSubscription($tenant->id, $guid, $entry, $settings);
            $result['subscriptions'][$subscription?->exists ? 'updated' : 'created']++;
            $syncedSubscriptionGuids[] = $guid;

            if (! $dryRun) {
                $attributes = $this->subscriptionAttributes($tenant->id, $customer, $entry, $settings['subscription_sync'], $guid);
                $subscription ??= new Subscription;
                $this->restoreIfTrashed($subscription);
                Log::info('LDAP subscription save starting.', [
                    'tenant_id' => $tenant->id,
                    'existing_subscription_id' => $subscription->exists ? $subscription->id : null,
                    'ldap_guid' => $guid,
                    'subscription_code' => $attributes['subscription_code'] ?? null,
                    'customer_id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'organization_id' => $customer->organization_id,
                    'plan_id' => $attributes['plan_id'] ?? null,
                    'billing_cycle' => $attributes['billing_cycle'] ?? null,
                    'billing_enabled' => $attributes['billing_enabled'] ?? null,
                    'status' => $attributes['status'] ?? null,
                    'pppoe_username' => $attributes['pppoe_username'] ?? null,
                    'ip_address' => $attributes['ip_address'] ?? null,
                    'mac_address' => $attributes['mac_address'] ?? null,
                    'dn' => $this->dn($entry),
                ]);
                $subscription->forceFill($attributes)->save();
                $this->syncOrganizationBillingForSubscription($subscription->fresh(['customer.organization.defaultPlan']), $settings['subscription_sync']);
                Log::info('LDAP subscription saved.', [
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $subscription->id,
                    'subscription_code' => $subscription->subscription_code,
                    'customer_id' => $customer->id,
                    'organization_id' => $customer->organization_id,
                    'plan_id' => $subscription->plan_id,
                    'billing_cycle' => $subscription->billing_cycle,
                    'billing_enabled' => $subscription->billing_enabled,
                    'ldap_guid' => $guid,
                ]);
            }
        }

        if (! $dryRun) {
            if ($selectedOuDns !== null) {
                $result['organizations']['missing'] = $this->handleMissingOrganizations($tenant->id, $syncedOrganizationGuids, $settings['connection']['missing_action'], $selectedOuDns);
                $result['customers']['missing'] = $this->handleMissingCustomers($tenant->id, $syncedCustomerGuids, $settings['connection']['missing_action'], $selectedOuDns);
                $result['subscriptions']['missing'] = $this->handleMissingSubscriptions($tenant->id, $syncedSubscriptionGuids, $settings['connection']['missing_action'], $selectedOuDns);
            } else {
                if (filled($settings['organization_sync']['base_dn'] ?? null) && filled($settings['organization_sync']['filter'] ?? null)) {
                    $result['organizations']['missing'] = $this->handleMissingOrganizations($tenant->id, $syncedOrganizationGuids, $settings['connection']['missing_action']);
                }

                $result['customers']['missing'] = $this->handleMissingCustomers($tenant->id, $syncedCustomerGuids, $settings['connection']['missing_action']);
                $result['subscriptions']['missing'] = $this->handleMissingSubscriptions($tenant->id, $syncedSubscriptionGuids, $settings['connection']['missing_action']);
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $syncSettings
     * @return Collection<int, LdapModel>
     */
    private function search(string $connection, array $syncSettings): Collection
    {
        if (! filled($syncSettings['base_dn'] ?? null) || ! filled($syncSettings['filter'] ?? null)) {
            return collect();
        }

        return Entry::on($connection)
            ->in($syncSettings['base_dn'])
            ->rawFilter($syncSettings['filter'])
            ->get($this->selects($syncSettings));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return Collection<int, array{dn: string, guid: string|null, name: string, path: string, selected: bool, entry: LdapModel|array<string, mixed>}>
     */
    public function discoverOrganizationalUnits(string $connection, array $settings): Collection
    {
        $baseDn = $settings['connection']['base_dn'] ?? null;

        if (! filled($baseDn)) {
            return collect();
        }

        $entries = Entry::on($connection)
            ->in($baseDn)
            ->rawFilter('(objectClass=organizationalUnit)')
            ->get(['dn', 'distinguishedName', 'objectGUID', 'ou', 'name', 'description']);

        return $this->normalizeOrganizationalUnits($entries, $settings);
    }

    /**
     * @param  iterable<int, LdapModel|array<string, mixed>>  $entries
     * @param  array<string, mixed>  $settings
     * @return Collection<int, array{dn: string, guid: string|null, name: string, path: string, selected: bool, entry: LdapModel|array<string, mixed>}>
     */
    public function normalizeOrganizationalUnits(iterable $entries, array $settings): Collection
    {
        $excludedDns = collect($settings['organization_sync']['excluded_ou_dns'] ?? [])
            ->map(fn (string $dn): string => $this->normalizeDn($dn))
            ->filter()
            ->flip();

        return collect($entries)
            ->map(function (LdapModel|array $entry) use ($excludedDns): ?array {
                $dn = $this->dn($entry);

                if (! filled($dn)) {
                    return null;
                }

                $name = $this->mapped($entry, 'ou') ?: $this->mapped($entry, 'name') ?: $this->firstDnPart($dn);
                $normalizedDn = $this->normalizeDn($dn);

                return [
                    'dn' => $dn,
                    'guid' => $this->entryGuid($entry),
                    'name' => $name,
                    'path' => $this->ouPath($dn),
                    'selected' => ! $excludedDns->has($normalizedDn),
                    'entry' => $entry,
                ];
            })
            ->filter()
            ->sortBy('path', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @param  Collection<int, array{dn: string, selected: bool, entry: LdapModel|array<string, mixed>}>  $organizationalUnits
     * @return Collection<int, array{dn: string, selected: bool, entry: LdapModel|array<string, mixed>}>
     */
    private function selectedOrganizationalUnits(Collection $organizationalUnits): Collection
    {
        return $organizationalUnits
            ->filter(fn (array $ou): bool => (bool) $ou['selected'])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $syncSettings
     * @param  array<int, string>  $baseDns
     * @return Collection<int, LdapModel>
     */
    private function searchAcrossBaseDns(string $connection, array $syncSettings, array $baseDns): Collection
    {
        if (! filled($syncSettings['filter'] ?? null)) {
            return collect();
        }

        $uniqueEntries = collect();
        $seenKeys = [];

        foreach ($baseDns as $baseDn) {
            if (! filled($baseDn)) {
                continue;
            }

            $entries = Entry::on($connection)
                ->in($baseDn)
                ->rawFilter($syncSettings['filter'])
                ->get($this->selects($syncSettings));

            foreach ($entries as $entry) {
                $key = $this->entryGuid($entry) ?: $this->normalizeDn((string) $this->dn($entry));

                if (! filled($key) || isset($seenKeys[$key])) {
                    continue;
                }

                $seenKeys[$key] = true;
                $uniqueEntries->push($entry);
            }
        }

        return $uniqueEntries->values();
    }

    /**
     * @param  array<string, mixed>  $syncSettings
     * @return array<int, string>
     */
    private function selects(array $syncSettings): array
    {
        return collect($syncSettings['map'] ?? [])
            ->merge(Arr::only($syncSettings, ['unique_attribute', 'match_attribute', 'customer_attribute', 'organization_attribute']))
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
    private function findOrganization(string $tenantId, string $guid, LdapModel|array $entry, array $settings): ?Organization
    {
        $query = Organization::withoutGlobalScopes()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($guid, $entry, $settings): void {
                $query->where('ldap_guid', $guid);

                $code = $this->mapped($entry, $settings['organization_sync']['map']['code'] ?? null);

                if (filled($code)) {
                    $query->orWhere('code', $code);
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
            Log::warning('LDAP subscription customer link is blank.', [
                'tenant_id' => $tenantId,
                'customer_attribute' => $settings['subscription_sync']['customer_attribute'] ?? null,
                'customer_match_field' => $field,
                'dn' => $this->dn($entry),
                'available_attributes' => $this->attributeNames($entry),
            ]);

            return null;
        }

        $customer = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where($field, $value)
            ->first();

        Log::info('LDAP subscription customer lookup completed.', [
            'tenant_id' => $tenantId,
            'customer_attribute' => $settings['subscription_sync']['customer_attribute'] ?? null,
            'customer_attribute_value' => $value,
            'customer_match_field' => $field,
            'customer_found' => (bool) $customer,
            'customer_id' => $customer?->id,
            'dn' => $this->dn($entry),
        ]);

        return $customer;
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     * @param  array<string, mixed>  $syncSettings
     * @return array<string, mixed>
     */
    private function organizationAttributes(string $tenantId, LdapModel|array $entry, array $syncSettings, string $guid): array
    {
        $code = $this->mapped($entry, $syncSettings['map']['code'] ?? null) ?: $guid;
        $name = $this->mapped($entry, $syncSettings['map']['name'] ?? null) ?: $code;

        return [
            'tenant_id' => $tenantId,
            'ldap_guid' => $guid,
            'ldap_domain' => $this->domain($entry),
            'ldap_dn' => $this->dn($entry),
            'ldap_synced_at' => now(),
            'code' => $code,
            'name' => $name,
            'description' => $this->mapped($entry, $syncSettings['map']['description'] ?? null),
            'status' => $this->status($this->mapped($entry, $syncSettings['map']['status'] ?? null), ['active', 'inactive'], 'active'),
        ];
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
        $organization = $this->customerOrganization($tenantId, $entry, $syncSettings);

        return [
            'tenant_id' => $tenantId,
            'organization_id' => $organization?->id,
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
     */
    private function customerOrganization(string $tenantId, LdapModel|array $entry, array $syncSettings): ?Organization
    {
        $attribute = $syncSettings['organization_attribute'] ?? null;
        $value = $this->mapped($entry, $attribute);
        $field = $syncSettings['organization_match_field'] ?? 'code';

        if (! filled($attribute) || ! filled($value)) {
            Log::info('LDAP customer organization link skipped.', [
                'tenant_id' => $tenantId,
                'organization_attribute' => $attribute,
                'organization_attribute_value' => $value,
                'organization_match_field' => $field,
                'dn' => $this->dn($entry),
            ]);

            return null;
        }

        $organization = Organization::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where($field, $value)
            ->first();

        Log::info('LDAP customer organization lookup completed.', [
            'tenant_id' => $tenantId,
            'organization_attribute' => $attribute,
            'organization_attribute_value' => $value,
            'organization_match_field' => $field,
            'organization_found' => (bool) $organization,
            'organization_id' => $organization?->id,
            'dn' => $this->dn($entry),
        ]);

        return $organization;
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     * @param  array<string, mixed>  $syncSettings
     * @return array<string, mixed>
     */
    private function subscriptionAttributes(string $tenantId, Customer $customer, LdapModel|array $entry, array $syncSettings, string $guid): array
    {
        $attributes = [
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

        $customer->loadMissing('organization.defaultPlan');

        if ($customer->organization?->billing_enabled) {
            $organization = $customer->organization;
            $attributes['plan_id'] = $organization->default_plan_id;
            $attributes['base_price'] = $organization->defaultPlan?->price ?? 0;
            $attributes['billing_cycle'] = $organization->default_billing_cycle;
            $attributes['billing_enabled'] = true;
            $attributes['billing_disabled_at'] = null;
            $attributes['grace_period_days'] = $organization->default_grace_period_days;

            Log::info('LDAP subscription organization billing defaults applied.', [
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'organization_id' => $organization->id,
                'default_plan_id' => $organization->default_plan_id,
                'default_billing_cycle' => $organization->default_billing_cycle,
                'default_grace_period_days' => $organization->default_grace_period_days,
                'subscription_guid' => $guid,
            ]);
        }

        return $attributes;
    }

    /**
     * @param  array<int, string>  $syncedGuids
     */
    private function handleMissingCustomers(string $tenantId, array $syncedGuids, string $action, ?array $selectedOuDns = null): int
    {
        if ($action === 'ignore') {
            return 0;
        }

        if ($selectedOuDns === []) {
            return 0;
        }

        $query = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('ldap_guid')
            ->when($syncedGuids !== [], fn ($query) => $query->whereNotIn('ldap_guid', $syncedGuids));

        $this->scopeQueryToOuDns($query, $selectedOuDns);

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
    private function handleMissingOrganizations(string $tenantId, array $syncedGuids, string $action, ?array $selectedOuDns = null): int
    {
        if ($action === 'ignore') {
            return 0;
        }

        if ($selectedOuDns === []) {
            return 0;
        }

        $query = Organization::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('ldap_guid')
            ->when($syncedGuids !== [], fn ($query) => $query->whereNotIn('ldap_guid', $syncedGuids));

        if ($selectedOuDns !== null) {
            $query->whereIn('ldap_dn', $selectedOuDns);
        }

        if ($action === 'soft_delete') {
            $organizations = $query->get();
            $organizations->each->delete();

            Log::info('LDAP missing organizations soft deleted.', [
                'tenant_id' => $tenantId,
                'count' => $organizations->count(),
            ]);

            return $organizations->count();
        }

        $count = $query->update(['status' => 'inactive']);

        Log::info('LDAP missing organizations marked inactive.', [
            'tenant_id' => $tenantId,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * @param  array<int, string>  $syncedGuids
     */
    private function handleMissingSubscriptions(string $tenantId, array $syncedGuids, string $action, ?array $selectedOuDns = null): int
    {
        if ($action === 'ignore') {
            return 0;
        }

        if ($selectedOuDns === []) {
            return 0;
        }

        $query = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('ldap_guid')
            ->when($syncedGuids !== [], fn ($query) => $query->whereNotIn('ldap_guid', $syncedGuids));

        $this->scopeQueryToOuDns($query, $selectedOuDns);

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
        if (strtolower((string) $attribute) === 'objectguid') {
            return $this->entryGuid($entry);
        }

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
    private function entryGuid(LdapModel|array $entry): ?string
    {
        if ($entry instanceof LdapModel && method_exists($entry, 'getConvertedGuid')) {
            $guid = $entry->getConvertedGuid();

            if (filled($guid)) {
                return $guid;
            }
        }

        return $this->mapped($entry, 'objectGUID') ?: $this->mapped($entry, 'objectguid');
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     * @param  array<string, mixed>  $context
     */
    private function logSkippedEntry(string $tenantId, string $type, string $reason, LdapModel|array $entry, array $context = []): void
    {
        Log::warning('LDAP sync skipped entry.', $context + [
            'tenant_id' => $tenantId,
            'type' => $type,
            'reason' => $reason,
            'dn' => $this->dn($entry),
        ]);
    }

    /**
     * @param  LdapModel|array<string, mixed>  $entry
     * @return array<int, string>
     */
    private function attributeNames(LdapModel|array $entry): array
    {
        if ($entry instanceof LdapModel) {
            return array_values(array_filter(array_keys($entry->getAttributes()), fn (string $attribute): bool => ! str_contains(strtolower($attribute), 'password')));
        }

        return array_values(array_filter(array_keys($entry), fn (string $attribute): bool => ! str_contains(strtolower($attribute), 'password')));
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

    private function normalizeDn(?string $dn): string
    {
        return strtolower(trim((string) $dn));
    }

    private function firstDnPart(string $dn): string
    {
        $firstPart = explode(',', $dn, 2)[0] ?? $dn;

        return trim((string) preg_replace('/^[a-zA-Z]+=/', '', $firstPart));
    }

    private function ouPath(string $dn): string
    {
        $parts = array_map('trim', explode(',', $dn));

        return collect($parts)
            ->filter(fn (string $part): bool => str_starts_with(strtolower($part), 'ou='))
            ->map(fn (string $part): string => trim(substr($part, 3)))
            ->reverse()
            ->implode(' / ') ?: $this->firstDnPart($dn);
    }

    private function scopeQueryToOuDns($query, ?array $selectedOuDns): void
    {
        if ($selectedOuDns === null) {
            return;
        }

        $query->where(function ($query) use ($selectedOuDns): void {
            foreach ($selectedOuDns as $dn) {
                $escapedDn = addcslashes($dn, '%_\\');

                $query->orWhere('ldap_dn', $dn)
                    ->orWhere('ldap_dn', 'like', '%,'.$escapedDn);
            }
        });
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
     * @param  array<string, mixed>  $syncSettings
     */
    private function syncOrganizationBillingForSubscription(Subscription $subscription, array $syncSettings): void
    {
        $subscription->loadMissing('customer.organization.defaultPlan');
        $organization = $subscription->customer?->organization;

        if (! $organization?->billing_enabled) {
            Log::info('LDAP subscription organization billing skipped.', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'organization_id' => $organization?->id,
                'reason' => $organization ? 'organization_billing_disabled' : 'customer_has_no_organization',
            ]);

            return;
        }

        if (! $organization->default_plan_id || ! $organization->defaultPlan) {
            Log::warning('LDAP subscription organization billing could not create plan item.', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'organization_id' => $organization->id,
                'reason' => 'organization_default_plan_missing',
            ]);

            return;
        }

        $item = $subscription->items()
            ->where('item_type', 'plan')
            ->oldest()
            ->first() ?? new SubscriptionItem(['subscription_id' => $subscription->id, 'item_type' => 'plan']);

        $item->fill([
            'description' => $organization->defaultPlan->name,
            'plan_id' => $organization->default_plan_id,
            'quantity' => 1,
            'unit_price' => $organization->defaultPlan->price,
            'discount_type' => $organization->default_discount_type,
            'discount_amount' => $organization->default_discount_amount,
            'tax_percentage' => $organization->default_tax_percentage,
            'recurring' => true,
            'billing_cycle' => $organization->default_billing_cycle,
        ]);
        $item->calculateTotals();
        $item->save();

        $subscription->calculateTotalPrice();

        Log::info('LDAP subscription organization billing plan item synced.', [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'subscription_code' => $subscription->subscription_code,
            'customer_id' => $subscription->customer_id,
            'organization_id' => $organization->id,
            'plan_id' => $organization->default_plan_id,
            'subscription_item_id' => $item->id,
            'line_total' => (float) $item->total,
            'subscription_total' => (float) $subscription->fresh()->total_price,
        ]);
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? $name, $parts[1] ?? null];
    }

    private function restoreIfTrashed(Customer|Organization|Subscription $model): void
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
            'organizations' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'missing' => 0],
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
