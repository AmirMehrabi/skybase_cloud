<?php

return [
    'console_host_enabled' => (bool) env('SKYBASE_CONSOLE_HOST_ENABLED', false),
    'console_host' => env('SKYBASE_CONSOLE_HOST', 'console.skybase.app'),
    'owner_console_enabled' => (bool) env('SKYBASE_OWNER_CONSOLE_ENABLED', false),
    'enabled' => (bool) env('SKYBASE_SAAS_BILLING_ENABLED', false),
    'currency' => 'USD',
    'dummy_gateway_enabled' => (bool) env('SKYBASE_DUMMY_GATEWAY_ENABLED', false),
    'packages' => [
        'free' => ['features' => ['cloud_hosting', 'automatic_updates', 'basic_support'], 'self_service' => true, 'name' => 'Free', 'subscriber_limit' => 40, 'monthly_price_minor' => 0],
        'starter' => ['features' => ['cloud_hosting', 'automatic_updates', 'basic_support', 'priority_support', 'advanced_monitoring'], 'self_service' => true, 'name' => 'Starter', 'subscriber_limit' => 150, 'monthly_price_minor' => 6900],
        'growth' => ['features' => ['cloud_hosting', 'automatic_updates', 'basic_support', 'priority_support', 'advanced_monitoring', 'api_access', 'custom_integrations'], 'self_service' => true, 'name' => 'Growth', 'subscriber_limit' => 300, 'monthly_price_minor' => 12900],
        'scale' => ['features' => ['cloud_hosting', 'automatic_updates', 'basic_support', 'priority_support', 'advanced_monitoring', 'api_access', 'custom_integrations', 'dedicated_support', 'advanced_reporting'], 'self_service' => true, 'name' => 'Scale', 'subscriber_limit' => 600, 'monthly_price_minor' => 23900],
        'business' => ['features' => ['cloud_hosting', 'automatic_updates', 'basic_support', 'priority_support', 'advanced_monitoring', 'api_access', 'custom_integrations', 'dedicated_support', 'advanced_reporting', 'account_manager', 'custom_training'], 'self_service' => true, 'name' => 'Business', 'subscriber_limit' => 1200, 'monthly_price_minor' => 39900],
        'carrier' => ['features' => ['cloud_hosting', 'automatic_updates', 'basic_support', 'priority_support', 'advanced_monitoring', 'api_access', 'custom_integrations', 'dedicated_support', 'advanced_reporting', 'account_manager', 'custom_training', 'founder_support', 'sla'], 'self_service' => true, 'name' => 'Carrier', 'subscriber_limit' => 2400, 'monthly_price_minor' => 74900],
        'enterprise' => ['features' => ['cloud_hosting', 'automatic_updates', 'basic_support', 'priority_support', 'advanced_monitoring', 'api_access', 'custom_integrations', 'dedicated_support', 'advanced_reporting', 'account_manager', 'custom_training', 'founder_support', 'sla'], 'self_service' => false, 'name' => 'Enterprise', 'subscriber_limit' => 5000, 'monthly_price_minor' => 130000],
    ],
];
