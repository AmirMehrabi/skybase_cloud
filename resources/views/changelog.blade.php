@extends('layouts.layout')

@section('title', 'Changelog | SkyBase Cloud')
@section('meta_description', 'Track SkyBase Cloud product updates, release notes, and platform improvements for MikroTik-focused ISP operators.')
@section('meta_keywords', 'SkyBase changelog, ISP software updates, MikroTik ISP platform release notes, NetFlow monitoring')
@section('og_title', 'SkyBase Cloud Changelog')
@section('og_description', 'Release notes and product updates for SkyBase Cloud.')
@section('og_url', url('/changelog'))
@section('body_class', 'bg-[#f6f1e8] text-slate-950')

@php
    $releases = [
        [
            'version' => '0.9.8',
            'date' => 'July 18, 2026',
            'title' => 'Work Orders, Smarter Support, and Smoother Operations',
            'summary' => 'This release gives your team a clearer way to move customer work from request to completion, with a new work order workspace, better support ownership, faster usage reporting, and more reliable subscription management.',
            'sections' => [
                'Added' => [
                    'A complete Work Orders workspace for installations, upgrades, repairs, surveys, and other field-service jobs.',
                    'Work order checklists, appointments, team and technician assignment, notes, materials, and photo or document evidence in one place.',
                    'A clear activity history so your team can see what changed, who handled it, and what still needs attention.',
                    'Direct links between support tickets, customers, subscriptions, and the work needed to resolve an issue.',
                    'Team and agent assignment for support tickets, with team queues when a ticket is not yet assigned to an individual.',
                ],
                'Changed' => [
                    'Support tickets now offer Team tickets and My tickets views, alongside clearer filtering by status, team, and agent.',
                    'Ticket pages now make customer, subscription, PPPoE, ownership, priority, SLA timing, replies, and internal notes easier to follow.',
                    'Usage reporting now loads large histories more efficiently, making daily usage views more responsive as your network grows.',
                    'Live subscription usage checks now avoid unnecessary repeated collection work, improving responsiveness during monitoring.',
                ],
                'Fixed' => [
                    'Subscription editing now correctly loads the saved IP pool and primary IP address.',
                    'IP pool subnet information now displays correctly instead of showing undefined values.',
                    'Authentication attempts on subscription pages now remain visible and are cleaned up reliably over time.',
                    'Work order and support permissions now better match each team member’s responsibilities.',
                ],
            ],
        ],
        [
            'version' => '0.9.7',
            'date' => 'July 3, 2026',
            'title' => 'Advanced Traffic Shaping, Customer Portal, and Usage Insights',
            'summary' => 'This release gives ISPs more control over service quality and billing while introducing a complete self-service portal where customers can follow their subscriptions, usage, invoices, and support activity.',
            'sections' => [
                'Added' => [
                    'Advanced traffic shaping for Plans, including configurable burst speeds, burst thresholds and duration, guaranteed minimum speeds, traffic priority, and queue type.',
                    'Data-cap actions that let you choose whether service continues normally or slows to a defined speed after the allowance is reached.',
                    'A self-service Customer Portal with an account dashboard, subscription details, invoices, support access, and profile management.',
                    'Download and upload usage graphs for every subscription, with clear historical views for both staff and customers.',
                    'A combined usage graph on the Customer Portal dashboard for a quick view across all of a customer’s subscriptions.',
                    'Billing tax settings for future invoices, including tax rates, invoice tax notes, and an option to display the business tax ID.',
                    'Profile and password management for both staff users and Customer Portal users.',
                    'Per-subscription automatic suspension controls for overdue invoices.',
                ],
                'Changed' => [
                    'Plan pages now present traffic-shaping settings and their effective service limits more clearly.',
                    'The Customer Portal dashboard now highlights online services, outstanding invoices, open support tickets, recent billing activity, and upcoming billing dates.',
                    'Customers can now open a secure detail page for each subscription to review service, connection, billing, and recent invoice information.',
                    'Support tickets can be linked to a subscription using its service identifier, making it easier to request help for the correct connection.',
                    'Subscription setup now keeps temporarily offline routers available for selection.',
                    'Usage reporting and live bandwidth charts now provide clearer time ranges, tooltips, totals, and empty-data states.',
                ],
                'Fixed' => [
                    'Improved the reliability of usage totals and graphs across different accounting setups.',
                    'Resolved Customer Portal sign-in and password-change edge cases.',
                    'Improved subscription billing, invoice tax calculations, and service provisioning consistency.',
                ],
            ],
        ],
        [
            'version' => '0.9.6',
            'date' => 'June 26, 2026',
            'title' => 'Role-Based Access Control and Permission Routing',
            'summary' => 'This release extends the existing roles system into a full RBAC layer with module-level permissions, clearer access denial handling, and first-page login routing for users without dashboard access.',
            'sections' => [
                'Added' => [
                    'Role-based access control built on the existing roles system, with per-module read, write, create, update, delete, export, import, and manage permissions.',
                    'English permission labels and descriptions for the full permission registry so access rules are easier to review and maintain.',
                    'Tenant role management screens for creating, editing, and assigning role-based permissions from the admin area.',
                    'Permission coverage across sidebar modules and management pages so navigation reflects the user’s actual access.',
                    'First-accessible-page routing after login for users who do not have dashboard access.',
                ],
                'Changed' => [
                    'Permission checks now return clear English denial messages instead of falling back to ambiguous or localized text.',
                    'Sidebar navigation now hides modules and pages the current user cannot access.',
                    'Dashboard entry behavior now redirects restricted users to the first page they are allowed to open.',
                    'Role update and access feedback messages were standardized across the admin experience.',
                ],
                'Fixed' => [
                    'Fixed ticketing and other tenant pages that depended on inconsistent permission or pivot behavior under restricted accounts.',
                    'Resolved Farsi-facing RBAC messages in app-facing permission and role flows.',
                    'Closed gaps where users with limited access could be blocked on login without a valid fallback route.',
                ],
            ],
        ],
        [
            'version' => '0.9.5',
            'date' => 'June 12, 2026',
            'title' => 'Access Points, Country Expansion, and Subscription Controls',
            'summary' => 'This release introduces wireless access point management, expands country and currency coverage, and adds new subscriber control features.',
            'sections' => [
                'Added' => [
                    'Wireless access point management with full create, edit, and tracking pages.',
                    'Assign wireless access points to customer subscriptions during setup and editing.',
                    'Sierra Leone added to country selection across all forms.',
                    'South African Rand added as a currency option for billing.',
                    'New comparison pages for Sonar and Splynx alternatives.',
                    'More reliable user disconnect with automatic fallback methods.',
                    'One-click disconnect button for active subscriptions.',
                    'Ability to release IP addresses from subscriptions.',
                    'Change subscriber usernames and passwords directly from the subscription page.',
                    'Improved real-time usage graphs for subscription monitoring.',
                ],
                'Changed' => [
                    'Subscription setup now lets you assign a wireless access point to each subscriber.',
                    'Country selection expanded with Sierra Leone across all forms.',
                    'Currency options expanded with South African Rand for billing.',
                ],
                'Fixed' => [
                    'Fixed IP address assignment issues.',
                    'General stability improvements.',
                ],
            ],
        ],
        [
            'version' => '0.9.4',
            'date' => 'June 6, 2026',
            'title' => 'CoA, Queue Infrastructure, and IP Selection Improvements',
            'summary' => 'This release sharpens disconnect handling, strengthens background processing, and refines IP pool selection across the platform.',
            'sections' => [
                'Added' => [
                    'RouterOS CoA support for the subscription disconnect flow, enabling faster remote session termination.',
                    'Redis and Horizon queue configuration to support background subscription jobs and worker processing.',
                    'Refined IP selection behavior for router pools and subscription assignment screens.',
                ],
                'Changed' => [
                    'Subscription reconciliation and router status handling were tightened for more reliable day-to-day operations.',
                    'Bulk delete, import/export, and monitoring flows were aligned with the latest workflow updates.',
                ],
                'Fixed' => [
                    'IP pool selection, CoA disconnect actions, and related coverage were polished.',
                    'Queue wiring and router/IPAM edge cases were corrected.',
                ],
            ],
        ],
        [
            'version' => '0.9.3',
            'date' => 'June 5, 2026',
            'title' => 'Router Status, Pagination, and IPAM Enhancements',
            'summary' => 'This release improves operational visibility and streamlines list, IP management, and bulk maintenance workflows.',
            'sections' => [
                'Added' => [
                    'Improved router status probing with ping fallback and failure tracking for unstable links.',
                    'Updated pagination across customers, subscriptions, organizations, and routers.',
                    'Bulk delete workflows for customers and subscriptions with run tracking.',
                ],
                'Changed' => [
                    'IP address and pool workflows were adjusted to handle larger import and correction scenarios.',
                    'Subscription search now matches PPPoE usernames across customer and subscription indexes.',
                ],
                'Fixed' => [
                    'Router pagination and status handling were stabilized.',
                    'IP pool assignment and reserved-address handling were tightened with updated tests.',
                ],
            ],
        ],
        [
            'version' => '0.9.0',
            'date' => 'June 2, 2026',
            'title' => 'Imports, Monitoring, and Customer Insights',
            'summary' => 'This release expands customer visibility, adds live monitoring, and strengthens import/export workflows.',
            'sections' => [
                'Added' => [
                    'Customer detail pages with notes, ticketing, and subscription overview sections.',
                    'Monitoring surfaces for latency, delay, and live subscription graphs.',
                    'Import and export workflows for customers, plans, and subscriptions.',
                ],
                'Changed' => [
                    'Subscription records gained IP route support and reactivation handling.',
                    'FreeRADIUS tenant fields were adjusted so imported records stay compatible with tenant-scoped data.',
                ],
                'Fixed' => [
                    'Subscription import paths now handle malformed rows and multi-record imports more reliably.',
                    'Provisioning and reconciliation flows were updated to keep RADIUS state aligned.',
                ],
            ],
        ],
        [
            'version' => '0.8.9',
            'date' => 'June 1, 2026',
            'title' => 'Notifications and White-Label Controls',
            'summary' => 'This release brings tenant notifications and branding controls to both public and authenticated areas.',
            'sections' => [
                'Added' => [
                    'Tenant notification module with in-app notification feeds and preferences.',
                    'Branding asset management for white-label logos and customer-facing presentation.',
                    'Cloud version toggle support for guest-facing access control.',
                ],
                'Changed' => [
                    'Admin and customer layouts now surface notification controls and branding updates consistently.',
                    'Settings pages were expanded to manage notification preferences and branding assets.',
                ],
                'Fixed' => [
                    'Layout rendering and settings forms were cleaned up to keep the new public and portal experiences consistent.',
                ],
            ],
        ],
        [
            'version' => '0.8.7',
            'date' => 'May 29, 2026',
            'title' => 'Ticketing, Support, and Suspension Enforcement',
            'summary' => 'This release adds the support desk workflow and automatically disconnects suspended subscribers.',
            'sections' => [
                'Added' => [
                    'Tenant ticketing and support workflows with message, attachment, and SLA support.',
                    'A richer ticket editor and message rendering for both staff and customers.',
                    'Automatic subscription disconnect enforcement when a subscription is suspended.',
                ],
                'Changed' => [
                    'Provisioning continues even when billing is disabled, keeping service activation independent from billing flags.',
                    'Subscription suspension now triggers a background disconnect flow and activity logging.',
                ],
                'Fixed' => [
                    'Ticket forms and support views were updated to match the new editor experience.',
                    'Suspension and provisioning tests were tightened around the new operational workflow.',
                ],
            ],
        ],
        [
            'version' => '0.8.3',
            'date' => 'May 24, 2026',
            'title' => 'Customer Portal Launch and Login Polish',
            'summary' => 'This release introduces the customer portal and refines login and validation behavior around it.',
            'sections' => [
                'Added' => [
                    'Customer portal login, dashboard, invoices, subscriptions, and support pages.',
                    'Portal authentication middleware and tenant-aware access checks.',
                    'Customer portal authentication tests and supporting layout updates.',
                ],
                'Changed' => [
                    'Customer creation and edit flows now expose the portal auth fields needed for login access.',
                    'Validation and layout components were aligned for the portal sign-in experience.',
                ],
                'Fixed' => [
                    'Portal login validation and related tenant auth behavior were corrected.',
                    'Shared form validation now renders cleaner client-side feedback.',
                ],
            ],
        ],
        [
            'version' => '0.8.0',
            'date' => 'May 23, 2026',
            'title' => 'Router NetFlow, Operational Alerts, and CRUD Polish',
            'summary' => 'This release expands router observability with MikroTik NetFlow support and cleans up browser form flows across core CRUD screens.',
            'sections' => [
                'Added' => [
                    'MikroTik NetFlow configuration on routers, including collector host, port, version, interfaces, and sampling settings.',
                    'RouterOS Traffic Flow setup service for enabling NetFlow and creating or updating MikroTik export targets through the RouterOS API.',
                    'Python-based NetFlow collector command, exposed through php artisan netflow:collect, for parsing flow exports and storing normalized records.',
                    'Tenant-scoped NetFlow flow storage, model, factory, migrations, and router summary service.',
                    'Router show-page NetFlow panel with setup status, test connection action, throughput, top sources, top destinations, protocols, and latest flows.',
                    'Focused NetFlow feature tests covering MikroTik setup, non-MikroTik rejection, packet test status, and tenant-scoped summary data.',
                    'Reusable admin flash alert component for success and error messages across create and edit flows.',
                    'LDAP diagnostic command and richer LDAP connection/sync logging for skipped entries and connection tests.',
                ],
                'Changed' => [
                    'Router create and edit screens now include NetFlow settings for MikroTik deployments.',
                    'Router create, update, and delete actions now redirect browser form submissions with flash messages while preserving JSON responses for AJAX requests.',
                    'Subscription create, update, and delete actions now provide browser redirect fallbacks while keeping existing JSON behavior for JavaScript flows.',
                    'Admin layout now renders shared flash alerts, reducing duplicated success/error blocks in individual pages.',
                    'Router validation now uses typed unique rules to avoid malformed validation strings during edits.',
                    'Environment example now documents NetFlow collector configuration values.',
                ],
                'Fixed' => [
                    'Router edit no longer displays raw JSON after a browser form submit.',
                    'Router IP validation no longer throws an internal Laravel validation error when editing an existing router.',
                    'Duplicate success banners were removed from router, settings, and VPN user screens now covered by the shared alert component.',
                ],
            ],
        ],
        [
            'version' => '0.7.0',
            'date' => 'May 8, 2026',
            'title' => 'VPN Users and Network Monitoring',
            'summary' => 'Introduced VPN account management and the first operational network monitoring dashboards.',
            'sections' => [
                'Added' => [
                    'OpenVPN user CRUD with active/online status fields and client onboarding details.',
                    'Network status dashboard with router health, uptime, active sessions, and alert visibility.',
                    'Bandwidth and usage dashboards powered by router, customer, and subscription data.',
                    'Network alert, bandwidth sample, and usage record factories and seed data for realistic local demos.',
                ],
                'Changed' => [
                    'Dashboard network cards were updated to show more operational router and customer signals.',
                    'Navigation was expanded to expose VPN users and network monitoring areas for tenant users.',
                ],
                'Fixed' => [
                    'Tenant scoping was tightened across new VPN and network monitoring models.',
                    'Network demo data now generates per tenant instead of relying on global sample records.',
                ],
            ],
        ],
        [
            'version' => '0.6.0',
            'date' => 'May 6, 2026',
            'title' => 'Billing, Invoices, Payments, and Credits',
            'summary' => 'Added the first complete billing workflow for tenant operators, from plans and subscriptions through invoices and payments.',
            'sections' => [
                'Added' => [
                    'Billing dashboard with revenue, invoice, payment, and overdue customer metrics.',
                    'Invoice, invoice item, payment, and customer credit models with tenant-aware relationships.',
                    'Recurring invoice generation command and billing service support for subscription billing.',
                    'Payment recording screens and customer credit tracking for account adjustments.',
                ],
                'Changed' => [
                    'Customers, plans, and subscriptions now include billing controls such as billing status, cycles, grace periods, and billable totals.',
                    'Reports were expanded with financial and usage-focused views for operators.',
                ],
                'Fixed' => [
                    'Billing records now use tenant-specific references to prevent collisions across tenants.',
                    'Subscription billing dates now remain consistent when billing is disabled or re-enabled.',
                ],
            ],
        ],
        [
            'version' => '0.5.0',
            'date' => 'Mar 20, 2026',
            'title' => 'Subscriptions and Router Assignment',
            'summary' => 'Connected customers, plans, routers, and service lifecycle management into a subscription workflow.',
            'sections' => [
                'Added' => [
                    'Subscription and subscription item models for recurring and one-time customer services.',
                    'Subscription create, edit, show, and index pages with customer, plan, router, site, IP, and PPPoE fields.',
                    'Router and plan relationships on customer and subscription records.',
                    'Connection type and IP management fields for router-managed, static, DHCP, and PPPoE services.',
                ],
                'Changed' => [
                    'Customer service fields were moved into subscriptions so customers can hold multiple services over time.',
                    'Plan forms now support router profile and service-level configuration details.',
                ],
                'Fixed' => [
                    'PPPoE username checks now account for tenant and existing subscription exclusions.',
                    'Subscription status transitions now preserve activation and cancellation dates more consistently.',
                ],
            ],
        ],
        [
            'version' => '0.4.0',
            'date' => 'Mar 18, 2026',
            'title' => 'IPAM and Router Operations',
            'summary' => 'Delivered the first network inventory foundation with routers, IP pools, and IP address management.',
            'sections' => [
                'Added' => [
                    'Router CRUD with vendor, model, API, SSH, monitoring, provisioning, status, and site metadata.',
                    'IP pool management with CIDR validation, capacity calculations, router assignment, and tenant ownership.',
                    'IP address inventory pages with reserve, block, release, and assignment actions.',
                    'Router sessions, queues, profiles, interfaces, IP pools, and logs placeholder routes for operational expansion.',
                ],
                'Changed' => [
                    'Tenant data generation now creates router and network records per ISP tenant.',
                    'Admin sidebar and network pages were expanded around router-first workflows.',
                ],
                'Fixed' => [
                    'IP pool uniqueness is enforced per tenant and network range.',
                    'IP pool delete flow blocks removal when assigned addresses are present.',
                ],
            ],
        ],
        [
            'version' => '0.3.0',
            'date' => 'Feb 21, 2026',
            'title' => 'Tenant Settings, Roles, and Activity',
            'summary' => 'Added the tenant administration layer needed for account settings, users, roles, and auditability.',
            'sections' => [
                'Added' => [
                    'Tenant user management screens for owner and staff accounts.',
                    'Settings sections for general company information, branding, email, and LDAP configuration.',
                    'Role and permission foundations for tenant-level access control.',
                    'Activity log models, formatting service, and reusable activity-log component.',
                ],
                'Changed' => [
                    'Middleware now initializes tenant context from the logged-in user before tenant queries run.',
                    'Admin layout gained a persistent sidebar and top navigation for authenticated tenant users.',
                ],
                'Fixed' => [
                    'Cross-tenant user access now aborts when the authenticated user does not belong to the current tenant.',
                    'Suspended and pending tenant statuses are handled before protected pages are rendered.',
                ],
            ],
        ],
        [
            'version' => '0.2.0',
            'date' => 'Feb 17, 2026',
            'title' => 'Tenant Registration and Customer CRM',
            'summary' => 'Established tenant onboarding and the first customer management workflows.',
            'sections' => [
                'Added' => [
                    'Tenant registration and login flows for single-domain SkyBase accounts.',
                    'Customer CRUD with customer type, contact details, billing type, status, and tenant assignment.',
                    'Customer factories and seeders for representative ISP account data.',
                    'Marketing contact and demo request capture forms.',
                ],
                'Changed' => [
                    'Application routes were organized into guest-facing marketing pages and authenticated tenant pages.',
                    'Customer forms were expanded with reusable validation and input patterns.',
                ],
                'Fixed' => [
                    'Customer creation now sets the tenant automatically for authenticated users.',
                    'Customer queries are scoped to the active tenant to prevent cross-tenant visibility.',
                ],
            ],
        ],
        [
            'version' => '0.1.0',
            'date' => 'Jan 19, 2026',
            'title' => 'Initial Cloud ISP Foundation',
            'summary' => 'Started the Laravel application foundation for a MikroTik-focused ISP management platform.',
            'sections' => [
                'Added' => [
                    'Laravel application shell with authentication, database, queue, cache, and Vite/Tailwind setup.',
                    'Public home, pricing, features, and contact pages for early SkyBase positioning.',
                    'Base models and migrations for users, tenants, plans, routers, settings, and roles.',
                    'Admin and marketing layouts with responsive navigation and brand styling.',
                ],
                'Changed' => [
                    'Default Laravel scaffolding was adapted around the SkyBase Cloud tenant and ISP domain.',
                    'Frontend assets were organized for Blade and Alpine.js driven pages.',
                ],
                'Fixed' => [
                    'Initial environment defaults were aligned for local development and testing.',
                    'Base route names were standardized for marketing, auth, and protected dashboard areas.',
                ],
            ],
        ],
    ];
@endphp

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0d2f35] py-16 text-white sm:py-20">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_18%_18%,rgba(34,197,94,0.26),transparent_28%),radial-gradient(circle_at_82%_12%,rgba(245,197,66,0.22),transparent_30%),linear-gradient(135deg,#09252b_0%,#0d2f35_48%,#123f3d_100%)]"></div>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-[#f5c542]">Release Notes</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl">SkyBase Cloud Changelog</h1>
                <p class="mt-5 text-lg leading-8 text-teal-50/85">
                    Product updates for ISP operators using SkyBase to manage MikroTik routers, subscribers, billing, IPAM, VPN users, and tenant operations.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-bold text-white">Current version 0.9.8</span>
                    <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-bold text-white">Updated July 18, 2026</span>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-[#f6f1e8] py-14 sm:py-16">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:px-8">
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-lg border border-slate-950/10 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Versions</p>
                    <nav class="mt-4 space-y-2">
                        @foreach($releases as $release)
                            <a href="#v{{ str_replace('.', '-', $release['version']) }}" class="block rounded-lg px-3 py-2 text-sm font-bold text-slate-700 hover:bg-[#fbf7ed] hover:text-slate-950">
                                {{ $release['version'] }}
                                <span class="block text-xs font-medium text-slate-500">{{ $release['date'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            <div class="space-y-8">
                @foreach($releases as $release)
                    <article id="v{{ str_replace('.', '-', $release['version']) }}" class="scroll-mt-28 rounded-lg border border-slate-950/10 bg-white p-6 shadow-sm sm:p-8">
                        <div class="flex flex-col gap-4 border-b border-slate-950/10 pb-6 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="rounded-full bg-[#0d2f35] px-3 py-1 text-sm font-bold text-white">v{{ $release['version'] }}</span>
                                    @if($loop->first)
                                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-800">Current</span>
                                    @endif
                                </div>
                                <h2 class="mt-4 text-2xl font-bold text-slate-950 sm:text-3xl">{{ $release['title'] }}</h2>
                                <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">{{ $release['summary'] }}</p>
                            </div>
                            <time class="shrink-0 text-sm font-bold text-slate-500">{{ $release['date'] }}</time>
                        </div>

                        <div class="mt-6 grid gap-6 lg:grid-cols-3">
                            @foreach($release['sections'] as $sectionTitle => $items)
                                <div class="rounded-lg border border-slate-950/10 bg-[#fbf7ed] p-5">
                                    <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-teal-800">{{ $sectionTitle }}</h3>
                                    <ul class="mt-4 space-y-3">
                                        @foreach($items as $item)
                                            <li class="flex gap-3 text-sm leading-6 text-slate-700">
                                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#f5c542]"></span>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
