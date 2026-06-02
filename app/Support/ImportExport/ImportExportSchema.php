<?php

namespace App\Support\ImportExport;

final class ImportExportSchema
{
    public const MODULE_PLANS = 'plans';

    public const MODULE_SUBSCRIPTIONS = 'subscriptions';

    /**
     * @return list<string>
     */
    public static function headings(string $module): array
    {
        return match ($module) {
            self::MODULE_PLANS => [
                'name',
                'internal_name',
                'description',
                'status',
                'visibility',
                'type',
                'category',
                'download_speed',
                'upload_speed',
                'burst_download',
                'burst_upload',
                'bandwidth_unit',
                'data_limit',
                'data_unit',
                'unlimited',
                'price',
                'currency',
                'billing_cycle',
                'grace_period_days',
                'setup_fee',
                'tax_profile',
                'router_profile',
                'ip_pool',
                'priority',
                'contract_required',
                'contract_duration',
                'available_from',
                'available_to',
                'notes',
            ],
            self::MODULE_SUBSCRIPTIONS => [
                'customer_code',
                'customer_type',
                'customer_name',
                'first_name',
                'last_name',
                'company_name',
                'national_id',
                'email',
                'phone',
                'mobile',
                'whatsapp',
                'address_line1',
                'address_line2',
                'city',
                'state',
                'postal_code',
                'country',
                'customer_status',
                'billing_type',
                'customer_billing_enabled',
                'balance',
                'credit_limit',
                'tax_exempt',
                'subscription_code',
                'subscription_name',
                'service_type',
                'plan_name',
                'router_name',
                'site',
                'connection_type',
                'ip_address',
                'mac_address',
                'ip_management',
                'pppoe_username',
                'pppoe_password',
                'base_price',
                'discount_amount',
                'discount_type',
                'tax_amount',
                'total_price',
                'billing_cycle',
                'billing_enabled',
                'grace_period_days',
                'next_billing_date',
                'status',
                'start_date',
                'end_date',
                'activation_date',
                'suspended_at',
                'cancelled_at',
                'notes',
            ],
            default => [],
        };
    }

    public static function exportFilename(string $module, int $runId): string
    {
        return sprintf('%s-export-%s-%d.xlsx', $module, now()->format('Ymd-His'), $runId);
    }

    public static function basePath(string $tenantId, int $runId): string
    {
        return "import-exports/{$tenantId}/{$runId}";
    }
}
