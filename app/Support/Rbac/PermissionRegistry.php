<?php

namespace App\Support\Rbac;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PermissionRegistry
{
    public const DENIED_MESSAGE = 'You do not have permission to access this area. Please contact the system administrator for access.';

    /**
     * @return array<string, array{label: string, description: string, actions: array<string, string>}>
     */
    public static function modules(): array
    {
        return [
            'dashboard' => ['label' => 'Dashboard', 'description' => 'Dashboard, search, and administrative notifications', 'actions' => self::actions(['read', 'actions'])],
            'organizations' => ['label' => 'Organizations', 'description' => 'Manage customer organizations and companies', 'actions' => self::actions(['read', 'write', 'delete'])],
            'customers' => ['label' => 'Customers', 'description' => 'Customer records, notes, status, and billing settings', 'actions' => self::actions()],
            'subscriptions' => ['label' => 'Subscriptions', 'description' => 'Services, connection operations, RADIUS, IP, and subscription billing', 'actions' => self::actions()],
            'support_tickets' => ['label' => 'Support Tickets', 'description' => 'View and respond to support tickets', 'actions' => self::actions(['read', 'write', 'actions'])],
            'support_teams' => ['label' => 'Support Teams', 'description' => 'Manage support teams and their members', 'actions' => self::actions()],
            'work_orders' => ['label' => 'Work Orders', 'description' => 'Schedule, execute, and provision ISP field work', 'actions' => self::actions(['read', 'write', 'delete', 'create', 'update', 'assign', 'schedule', 'execute', 'provision', 'complete', 'cancel', 'manage'])],
            'plans' => ['label' => 'Plans', 'description' => 'Define and import or export service plans', 'actions' => self::actions()],
            'billing' => ['label' => 'Billing', 'description' => 'Billing dashboard, invoices, payments, credits, and financial reports', 'actions' => self::actions()],
            'ipam' => ['label' => 'IP Management', 'description' => 'IPAM dashboard, pools, IP addresses, and IP release actions', 'actions' => self::actions()],
            'sites' => ['label' => 'Sites', 'description' => 'Manage sites and the network map', 'actions' => self::actions()],
            'routers' => ['label' => 'Routers', 'description' => 'Manage routers, NetFlow, monitoring, and RouterOS tools', 'actions' => self::actions()],
            'access_points' => ['label' => 'Access Points', 'description' => 'Manage access points and their router links', 'actions' => self::actions()],
            'vpn_users' => ['label' => 'VPN Users', 'description' => 'Manage VPN users', 'actions' => self::actions()],
            'network' => ['label' => 'Network', 'description' => 'Bandwidth, data usage, network status, and monitoring', 'actions' => self::actions(['read', 'actions'])],
            'reports' => ['label' => 'Reports', 'description' => 'Usage and financial reports', 'actions' => self::actions(['read'])],
            'users' => ['label' => 'Users', 'description' => 'Manage tenant users and their notification settings', 'actions' => self::actions()],
            'user_groups' => ['label' => 'User Groups', 'description' => 'Partition tenant users and operational data', 'actions' => self::actions(['read', 'write', 'delete'])],
            'roles' => ['label' => 'Roles', 'description' => 'Manage roles and system access permissions', 'actions' => self::actions()],
            'settings' => ['label' => 'Settings', 'description' => 'General settings, branding, email, notifications, and LDAP', 'actions' => self::actions(['read', 'write', 'delete', 'actions'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function allPermissions(): array
    {
        return collect(self::modules())
            ->flatMap(function (array $module, string $key): array {
                return collect(array_keys($module['actions']))
                    ->mapWithKeys(fn (string $action): array => ["{$key}.{$action}" => "{$module['label']} - {$module['actions'][$action]}"])
                    ->all();
            })
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function sanitizePermissions(array $permissions): array
    {
        if (in_array('*', $permissions, true)) {
            return ['*'];
        }

        $valid = array_keys(self::allPermissions());

        return collect($permissions)
            ->flatten()
            ->filter(fn (mixed $permission): bool => is_string($permission) && in_array($permission, $valid, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function equivalentPermissions(string $permission): array
    {
        return match ($permission) {
            'work_orders.write' => ['work_orders.write', 'work_orders.create', 'work_orders.update'],
            'work_orders.create', 'work_orders.update' => [$permission, 'work_orders.write'],
            'work_orders.delete' => ['work_orders.delete', 'work_orders.manage'],
            'work_orders.manage' => ['work_orders.manage', 'work_orders.delete'],
            default => [$permission],
        };
    }

    public static function routePermission(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        foreach (self::routeMap() as $pattern => $permission) {
            if (Str::is($pattern, $routeName)) {
                return $permission;
            }
        }

        return null;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function defaultRolePermissions(): array
    {
        $all = array_keys(self::allPermissions());

        return [
            'owner' => ['*'],
            'admin' => $all,
            'billing' => self::only([
                'dashboard.read',
                'customers.read',
                'customers.write',
                'subscriptions.read',
                'subscriptions.write',
                'plans.read',
                'billing.read',
                'billing.write',
                'billing.actions',
                'reports.read',
            ]),
            'support' => self::only([
                'dashboard.read',
                'organizations.read',
                'customers.read',
                'customers.write',
                'subscriptions.read',
                'subscriptions.write',
                'subscriptions.actions',
                'support_tickets.read',
                'support_tickets.write',
                'support_tickets.actions',
                'support_teams.read',
                'work_orders.read',
                'work_orders.write',
                'work_orders.assign',
                'work_orders.schedule',
                'work_orders.execute',
                'work_orders.complete',
                'work_orders.cancel',
                'plans.read',
                'ipam.read',
                'routers.read',
                'access_points.read',
                'network.read',
            ]),
            'noc' => self::only([
                'dashboard.read',
                'subscriptions.read',
                'subscriptions.actions',
                'ipam.read',
                'ipam.write',
                'ipam.actions',
                'sites.read',
                'sites.write',
                'routers.read',
                'routers.write',
                'routers.actions',
                'access_points.read',
                'access_points.write',
                'vpn_users.read',
                'vpn_users.write',
                'network.read',
                'network.actions',
                'work_orders.read',
                'work_orders.write',
                'work_orders.assign',
                'work_orders.schedule',
                'work_orders.execute',
                'work_orders.provision',
                'work_orders.complete',
                'work_orders.cancel',
                'reports.read',
            ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function routeMap(): array
    {
        return [
            'dashboard' => 'dashboard.read',
            'search.resources' => 'dashboard.read',
            'notifications.index' => 'dashboard.read',
            'notifications.read' => 'dashboard.actions',
            'notifications.read-all' => 'dashboard.actions',
            'notifications.archive' => 'dashboard.actions',

            'organizations.index' => 'organizations.read',
            'organizations.data' => 'organizations.read',
            'organizations.stats' => 'organizations.read',
            'organizations.show' => 'organizations.read',
            'organizations.create' => 'organizations.write',
            'organizations.store' => 'organizations.write',
            'organizations.edit' => 'organizations.write',
            'organizations.update' => 'organizations.write',
            'organizations.destroy' => 'organizations.delete',

            'customers.index' => 'customers.read',
            'customers.data' => 'customers.read',
            'customers.filter-options' => 'customers.read',
            'customers.stats' => 'customers.read',
            'customers.show' => 'customers.read',
            'customers.create' => 'customers.write',
            'customers.store' => 'customers.write',
            'customers.edit' => 'customers.write',
            'customers.update' => 'customers.write',
            'customers.notifications.update' => 'customers.write',
            'customers.notes.store' => 'customers.write',
            'customers.billing.update' => 'customers.write',
            'customers.suspend' => 'customers.actions',
            'customers.activate' => 'customers.actions',
            'customers.destroy' => 'customers.delete',
            'customers.bulk-destroy' => 'customers.delete',

            'subscriptions.index' => 'subscriptions.read',
            'subscriptions.data' => 'subscriptions.read',
            'subscriptions.stats' => 'subscriptions.read',
            'subscriptions.import-export-runs' => 'subscriptions.read',
            'subscriptions.check-pppoe-username' => 'subscriptions.read',
            'subscriptions.show' => 'subscriptions.read',
            'subscriptions.suggest-ip' => 'subscriptions.read',
            'subscriptions.bandwidth.live' => 'subscriptions.read',
            'subscriptions.bandwidth.history' => 'subscriptions.read',
            'subscriptions.create' => 'subscriptions.write',
            'subscriptions.store' => 'subscriptions.write',
            'subscriptions.edit' => 'subscriptions.write',
            'subscriptions.update' => 'subscriptions.write',
            'subscriptions.billing.update' => 'subscriptions.write',
            'subscriptions.export' => 'subscriptions.write',
            'subscriptions.import' => 'subscriptions.write',
            'subscriptions.import-ip-addresses' => 'subscriptions.write',
            'subscriptions.ip-routes.sync' => 'subscriptions.actions',
            'subscriptions.suspend' => 'subscriptions.actions',
            'subscriptions.kill-session' => 'subscriptions.actions',
            'subscriptions.activate' => 'subscriptions.actions',
            'subscriptions.generate-invoice' => 'subscriptions.actions',
            'subscriptions.cancel' => 'subscriptions.delete',
            'subscriptions.destroy' => 'subscriptions.delete',
            'subscriptions.bulk-destroy' => 'subscriptions.delete',

            'support.tickets.index' => 'support_tickets.read',
            'support.tickets.show' => 'support_tickets.read',
            'support.tickets.attachments.download' => 'support_tickets.read',
            'support.tickets.create' => 'support_tickets.write',
            'support.tickets.store' => 'support_tickets.write',
            'support.tickets.reply' => 'support_tickets.write',
            'support.tickets.status' => 'support_tickets.actions',
            'support.tickets.priority' => 'support_tickets.actions',
            'support.tickets.assign' => 'support_tickets.actions',
            'support.tickets.team' => 'support_tickets.actions',

            'support.teams.index' => 'support_teams.read',
            'support.teams.create' => 'support_teams.write',
            'support.teams.store' => 'support_teams.write',
            'support.teams.edit' => 'support_teams.write',
            'support.teams.update' => 'support_teams.write',
            'support.teams.destroy' => 'support_teams.delete',

            'work-orders.index' => 'work_orders.read',
            'work-orders.show' => 'work_orders.read',
            'work-orders.attachments.download' => 'work_orders.read',
            'work-orders.create' => 'work_orders.write',
            'work-orders.store' => 'work_orders.write',
            'work-orders.edit' => 'work_orders.write',
            'work-orders.update' => 'work_orders.write',
            'work-orders.destroy' => 'work_orders.delete',
            'work-orders.assign' => 'work_orders.assign',
            'work-orders.schedule' => 'work_orders.schedule',
            'work-orders.transition' => 'work_orders.execute',
            'work-orders.tasks.update' => 'work_orders.execute',
            'work-orders.notes.store' => 'work_orders.write',
            'work-orders.materials.store' => 'work_orders.execute',
            'work-orders.attachments.store' => 'work_orders.write',
            'work-orders.provision' => 'work_orders.provision',

            'plans.index' => 'plans.read',
            'plans.import-export-runs' => 'plans.read',
            'plans.show' => 'plans.read',
            'plans.create' => 'plans.write',
            'plans.store' => 'plans.write',
            'plans.edit' => 'plans.write',
            'plans.update' => 'plans.write',
            'plans.export' => 'plans.write',
            'plans.import' => 'plans.write',
            'plans.destroy' => 'plans.delete',
            'import-export.*' => 'subscriptions.read',

            'billing.dashboard' => 'billing.read',
            'billing.invoices.index' => 'billing.read',
            'billing.invoices.show' => 'billing.read',
            'billing.invoices.create' => 'billing.write',
            'billing.invoices.edit' => 'billing.write',
            'billing.invoices.payments.store' => 'billing.write',
            'billing.invoices.generate-recurring' => 'billing.actions',
            'billing.invoices.cancel' => 'billing.delete',
            'billing.payments.index' => 'billing.read',
            'billing.payments.show' => 'billing.read',
            'billing.payments.store' => 'billing.write',
            'billing.credits' => 'billing.read',
            'billing.credits.store' => 'billing.write',
            'billing.reports' => 'billing.read',

            'ipam.dashboard' => 'ipam.read',
            'ipam.check-ip' => 'ipam.read',
            'ipam.pools.index' => 'ipam.read',
            'ipam.pools.show' => 'ipam.read',
            'ipam.ips.index' => 'ipam.read',
            'ipam.ips.show' => 'ipam.read',
            'ipam.pools.create' => 'ipam.write',
            'ipam.pools.store' => 'ipam.write',
            'ipam.pools.edit' => 'ipam.write',
            'ipam.pools.update' => 'ipam.write',
            'ipam.pools.ip-addresses.release' => 'ipam.actions',
            'ipam.pools.destroy' => 'ipam.delete',

            'sites.index' => 'sites.read',
            'sites.data' => 'sites.read',
            'sites.stats' => 'sites.read',
            'sites.map-data' => 'sites.read',
            'sites.show' => 'sites.read',
            'sites.create' => 'sites.write',
            'sites.store' => 'sites.write',
            'sites.edit' => 'sites.write',
            'sites.update' => 'sites.write',
            'sites.destroy' => 'sites.delete',

            'routers.index' => 'routers.read',
            'routers.data' => 'routers.read',
            'routers.filter-options' => 'routers.read',
            'routers.stats' => 'routers.read',
            'routers.show' => 'routers.read',
            'routers.netflow.data' => 'routers.read',
            'routers.monitoring.data' => 'routers.read',
            'routers.sessions' => 'routers.read',
            'routers.queues' => 'routers.read',
            'routers.profiles' => 'routers.read',
            'routers.interfaces' => 'routers.read',
            'routers.ip-pools' => 'routers.read',
            'routers.logs' => 'routers.read',
            'routers.create' => 'routers.write',
            'routers.store' => 'routers.write',
            'routers.edit' => 'routers.write',
            'routers.update' => 'routers.write',
            'routers.netflow.setup' => 'routers.actions',
            'routers.netflow.test' => 'routers.actions',
            'routers.destroy' => 'routers.delete',

            'access-points.index' => 'access_points.read',
            'access-points.data' => 'access_points.read',
            'access-points.filter-options' => 'access_points.read',
            'access-points.stats' => 'access_points.read',
            'access-points.by-router' => 'access_points.read',
            'access-points.show' => 'access_points.read',
            'access-points.create' => 'access_points.write',
            'access-points.store' => 'access_points.write',
            'access-points.edit' => 'access_points.write',
            'access-points.update' => 'access_points.write',
            'access-points.destroy' => 'access_points.delete',

            'vpn-users.index' => 'vpn_users.read',
            'vpn-users.data' => 'vpn_users.read',
            'vpn-users.stats' => 'vpn_users.read',
            'vpn-users.show' => 'vpn_users.read',
            'vpn-users.create' => 'vpn_users.write',
            'vpn-users.store' => 'vpn_users.write',
            'vpn-users.edit' => 'vpn_users.write',
            'vpn-users.update' => 'vpn_users.write',
            'vpn-users.destroy' => 'vpn_users.delete',

            'network.bandwidth' => 'network.read',
            'network.bandwidth.data' => 'network.read',
            'network.data-usage' => 'network.read',
            'network.status' => 'network.read',
            'network.monitoring' => 'network.read',
            'network.monitoring.data' => 'network.read',

            'reports.usage' => 'reports.read',
            'reports.financial' => 'reports.read',

            'admin.tenant.users.index' => 'users.read',
            'admin.tenant.users.show' => 'users.read',
            'admin.tenant.users.create' => 'users.write',
            'admin.tenant.users.store' => 'users.write',
            'admin.tenant.users.edit' => 'users.write',
            'admin.tenant.users.update' => 'users.write',
            'admin.tenant.users.notifications.update' => 'users.write',
            'admin.tenant.users.destroy' => 'users.delete',

            'admin.tenant.user-groups.index' => 'user_groups.read',
            'admin.tenant.user-groups.show' => 'user_groups.read',
            'admin.tenant.user-groups.create' => 'user_groups.write',
            'admin.tenant.user-groups.store' => 'user_groups.write',
            'admin.tenant.user-groups.edit' => 'user_groups.write',
            'admin.tenant.user-groups.update' => 'user_groups.write',
            'admin.tenant.user-groups.destroy' => 'user_groups.delete',

            'admin.tenant.roles.index' => 'roles.read',
            'admin.tenant.roles.show' => 'roles.read',
            'admin.tenant.roles.create' => 'roles.write',
            'admin.tenant.roles.store' => 'roles.write',
            'admin.tenant.roles.edit' => 'roles.write',
            'admin.tenant.roles.update' => 'roles.write',
            'admin.tenant.roles.destroy' => 'roles.delete',

            'settings.index' => 'settings.read',
            'settings.update.*' => 'settings.write',
            'settings.test.*' => 'settings.actions',
            'settings.discover.*' => 'settings.actions',
            'settings.preview.*' => 'settings.actions',
            'settings.sync.*' => 'settings.actions',
            'settings.delete.asset' => 'settings.delete',
        ];
    }

    /**
     * @return list<string>
     */
    public static function landingRoutes(): array
    {
        return [
            'dashboard',
            'organizations.index',
            'customers.index',
            'subscriptions.index',
            'support.tickets.index',
            'support.teams.index',
            'work-orders.index',
            'plans.index',
            'billing.dashboard',
            'ipam.dashboard',
            'sites.index',
            'routers.index',
            'access-points.index',
            'vpn-users.index',
            'network.bandwidth',
            'reports.usage',
            'admin.tenant.users.index',
            'admin.tenant.user-groups.index',
            'admin.tenant.roles.index',
            'settings.index',
        ];
    }

    public static function firstAccessibleRoute(User $user): ?string
    {
        foreach (self::landingRoutes() as $routeName) {
            if ($user->canAccessRoute($routeName)) {
                return $routeName;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $actions
     * @return array<string, string>
     */
    private static function actions(array $actions = ['read', 'write', 'delete', 'actions']): array
    {
        $labels = [
            'read' => 'Read',
            'write' => 'Write',
            'delete' => 'Delete',
            'actions' => 'Actions',
            'create' => 'Create',
            'update' => 'Update',
            'assign' => 'Assign',
            'schedule' => 'Schedule',
            'execute' => 'Execute',
            'provision' => 'Provision',
            'complete' => 'Complete',
            'cancel' => 'Cancel',
            'manage' => 'Manage all',
        ];

        return Arr::only($labels, $actions);
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private static function only(array $permissions): array
    {
        return collect($permissions)
            ->filter(fn (string $permission): bool => array_key_exists($permission, self::allPermissions()))
            ->values()
            ->all();
    }
}
