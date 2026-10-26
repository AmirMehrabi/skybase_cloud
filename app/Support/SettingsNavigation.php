<?php

namespace App\Support;

class SettingsNavigation
{
    /** @return array<string, array{title: string, description: string, icon: string, group: string, route: string, parameters: array<string, string>}> */
    public static function sections(): array
    {
        $sections = [
            'general' => ['title' => 'Company & localization', 'description' => 'Company details, contact information, currency, language and timezone.', 'icon' => 'building', 'group' => 'Workspace'],
            'branding' => ['title' => 'Branding & appearance', 'description' => 'Make SkyBase your own with company logos, brand colors and portal assets.', 'icon' => 'palette', 'group' => 'Workspace'],
            'billing' => ['title' => 'Billing & taxes', 'description' => 'Set default tax rates, invoice labels and billing preferences.', 'icon' => 'receipt', 'group' => 'Workspace'],
            'customer-portal' => ['title' => 'Customer portal', 'description' => 'Choose which account and billing details customers can see in their portal.', 'icon' => 'users', 'group' => 'Workspace'],
            'email' => ['title' => 'Email connections', 'description' => 'Connect outgoing SMTP and incoming mailboxes, then test authentication and delivery.', 'icon' => 'mail', 'group' => 'Communication'],
            'sms' => ['title' => 'SMS gateway', 'description' => 'Connect Twilio, SMSPortal or Kavenegar and manage sender and delivery limits.', 'icon' => 'message', 'group' => 'Communication'],
            'notifications' => ['title' => 'Notification preferences', 'description' => 'Choose delivery channels and the events your staff and customers hear about.', 'icon' => 'bell', 'group' => 'Communication'],
            'users' => ['title' => 'Team members', 'description' => 'Invite staff, manage accounts and keep your team’s access up to date.', 'icon' => 'users', 'group' => 'Access & integrations', 'route' => 'admin.tenant.users.index'],
            'roles' => ['title' => 'Roles & permissions', 'description' => 'Define what each role can view, change and manage across your workspace.', 'icon' => 'shield', 'group' => 'Access & integrations', 'route' => 'admin.tenant.roles.index'],
            'user-groups' => ['title' => 'User groups', 'description' => 'Organize staff groups and control which customer and network data they can access.', 'icon' => 'layers', 'group' => 'Access & integrations', 'route' => 'admin.tenant.user-groups.index'],
            'ldap' => ['title' => 'Directory integration', 'description' => 'Connect Active Directory or LDAP and configure synchronization and authentication.', 'icon' => 'network', 'group' => 'Access & integrations'],
        ];
        foreach ($sections as $key => &$section) {
            $section['route'] ??= $key === 'sms' ? 'settings.sms' : 'settings.section';
            $section['parameters'] = $section['route'] === 'settings.section' ? ['section' => $key] : [];
        }

        return $sections;
    }
}
