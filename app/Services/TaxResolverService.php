<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Setting;

class TaxResolverService
{
    /**
     * @return array{enabled: bool, name: string, percentage: float, show_tax_id_on_invoice: bool, invoice_note: string|null}
     */
    public function settings(?string $tenantId = null): array
    {
        $settings = Setting::get('billing.tax', [], $tenantId);

        if (! is_array($settings)) {
            $settings = [];
        }

        return [
            'enabled' => (bool) ($settings['enabled'] ?? false),
            'name' => (string) ($settings['name'] ?? 'Tax'),
            'percentage' => (float) ($settings['percentage'] ?? 0),
            'show_tax_id_on_invoice' => (bool) ($settings['show_tax_id_on_invoice'] ?? false),
            'invoice_note' => filled($settings['invoice_note'] ?? null) ? (string) $settings['invoice_note'] : null,
        ];
    }

    /**
     * @return array{percentage: float, label: string, exempt: bool, exemption_reason: string|null}
     */
    public function resolve(Customer $customer, ?Plan $plan = null, ?string $itemType = null, ?float $manualPercentage = null): array
    {
        $customer->loadMissing('organization');

        if ($customer->tax_exempt) {
            return $this->result(0, 'Tax exempt', true, 'customer_tax_exempt');
        }

        $organization = $customer->organization;

        if ($organization?->billing_enabled) {
            return $this->forOrganization($organization);
        }

        $settings = $this->settings((string) $customer->tenant_id);

        if (! $settings['enabled']) {
            return $this->result(0, $settings['name'], false, null);
        }

        if ($manualPercentage !== null && $itemType !== 'plan') {
            return $this->result($manualPercentage, $settings['name'], false, null);
        }

        return $this->result($settings['percentage'], $settings['name'], false, null);
    }

    public function calculate(float|int|string|null $amount, float|int|string|null $percentage): float
    {
        return round(max(0, (float) $amount) * (max(0, (float) $percentage) / 100), 2);
    }

    /**
     * @return array{percentage: float, label: string, exempt: bool, exemption_reason: string|null}
     */
    protected function forOrganization(Organization $organization): array
    {
        return $this->result((float) $organization->default_tax_percentage, 'Organization tax', false, null);
    }

    /**
     * @return array{percentage: float, label: string, exempt: bool, exemption_reason: string|null}
     */
    protected function result(float $percentage, string $label, bool $exempt, ?string $reason): array
    {
        return [
            'percentage' => round(max(0, $percentage), 2),
            'label' => $label,
            'exempt' => $exempt,
            'exemption_reason' => $reason,
        ];
    }
}
