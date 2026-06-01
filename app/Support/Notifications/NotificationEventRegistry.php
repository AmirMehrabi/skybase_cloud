<?php

namespace App\Support\Notifications;

class NotificationEventRegistry
{
    public const TICKET_CREATED = 'support.ticket_created';

    public const TICKET_CUSTOMER_REPLY = 'support.customer_reply';

    public const TICKET_STAFF_REPLY = 'support.staff_reply';

    public const INVOICE_CREATED = 'billing.invoice_created';

    public const INVOICE_OVERDUE = 'billing.invoice_overdue';

    public const PAYMENT_RECEIVED = 'billing.payment_received';

    public const SUBSCRIPTION_SUSPENDED = 'subscription.suspended';

    public const SUBSCRIPTION_ACTIVATED = 'subscription.activated';

    public const ROUTER_DEGRADED = 'network.router_degraded';

    public const USAGE_THRESHOLD = 'usage.threshold_reached';

    public const OPERATIONAL_FAILURE = 'system.operational_failure';

    /**
     * @return array<string, array{label: string, category: string, severity: string, critical: bool, roles: list<string>}>
     */
    public static function events(): array
    {
        return [
            self::TICKET_CREATED => ['label' => 'Support ticket created', 'category' => 'support', 'severity' => 'info', 'critical' => false, 'roles' => ['owner', 'admin', 'support']],
            self::TICKET_CUSTOMER_REPLY => ['label' => 'Customer replied to a ticket', 'category' => 'support', 'severity' => 'info', 'critical' => false, 'roles' => ['owner', 'admin', 'support']],
            self::TICKET_STAFF_REPLY => ['label' => 'Staff replied to a ticket', 'category' => 'support', 'severity' => 'info', 'critical' => false, 'roles' => []],
            self::INVOICE_CREATED => ['label' => 'Invoice created', 'category' => 'billing', 'severity' => 'info', 'critical' => false, 'roles' => []],
            self::INVOICE_OVERDUE => ['label' => 'Invoice overdue', 'category' => 'billing', 'severity' => 'warning', 'critical' => false, 'roles' => ['owner', 'admin', 'billing']],
            self::PAYMENT_RECEIVED => ['label' => 'Payment received', 'category' => 'billing', 'severity' => 'success', 'critical' => false, 'roles' => ['owner', 'admin', 'billing']],
            self::SUBSCRIPTION_SUSPENDED => ['label' => 'Subscription suspended', 'category' => 'subscription', 'severity' => 'warning', 'critical' => false, 'roles' => ['owner', 'admin', 'support', 'noc']],
            self::SUBSCRIPTION_ACTIVATED => ['label' => 'Subscription activated', 'category' => 'subscription', 'severity' => 'success', 'critical' => false, 'roles' => ['owner', 'admin', 'support', 'noc']],
            self::ROUTER_DEGRADED => ['label' => 'Router offline or degraded', 'category' => 'network', 'severity' => 'critical', 'critical' => true, 'roles' => ['owner', 'admin', 'noc']],
            self::USAGE_THRESHOLD => ['label' => 'Usage threshold reached', 'category' => 'usage', 'severity' => 'warning', 'critical' => false, 'roles' => ['owner', 'admin', 'support']],
            self::OPERATIONAL_FAILURE => ['label' => 'Operational failure', 'category' => 'system', 'severity' => 'critical', 'critical' => true, 'roles' => ['owner', 'admin', 'noc']],
        ];
    }

    /**
     * @return array{label: string, category: string, severity: string, critical: bool, roles: list<string>}
     */
    public static function event(string $key): array
    {
        return self::events()[$key] ?? [
            'label' => str($key)->headline()->toString(),
            'category' => 'system',
            'severity' => 'info',
            'critical' => false,
            'roles' => ['owner', 'admin'],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function defaultRules(): array
    {
        return collect(self::events())
            ->mapWithKeys(fn (array $event, string $key): array => [$key => true])
            ->all();
    }
}
