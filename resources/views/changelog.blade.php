@extends('layouts.layout')

@section('title', 'What’s new | SkyBase Cloud Changelog')
@section('meta_description', 'Track SkyBase Cloud product updates, release notes, and platform improvements for MikroTik-focused ISP operators.')
@section('meta_keywords', 'SkyBase changelog, ISP software updates, MikroTik ISP platform release notes, NetFlow monitoring')
@section('og_title', 'SkyBase Cloud Changelog')
@section('og_description', 'Release notes and product updates for SkyBase Cloud.')
@section('og_url', url('/changelog'))
@section('body_class', 'bg-[#fffdf8] text-[#17211f]')

@php
    $releases = [
        [
            'version' => '1.0.0',
            'date' => 'October 30, 2026',
            'title' => 'A Clearer Start and More Control Over Daily Operations',
            'summary' => 'SkyBase 1.0 brings together a guided first-subscriber setup, customer communications, deeper service insights, and more flexible account management. This release also improves billing accuracy, usage reporting, and account security across everyday ISP workflows.',
            'highlights' => [
                ['title' => 'Get your first subscriber online', 'copy' => 'Follow the PPPoE or Hotspot guide, from connecting your router to testing the customer’s login.', 'route' => 'guide', 'link' => 'Follow the setup guide'],
                ['title' => 'Keep customers informed', 'copy' => 'Send campaigns, reuse message templates, and follow email and SMS delivery from one workspace.'],
                ['title' => 'Understand each service', 'copy' => 'See detailed traffic graphs and clearer usage history when you need to investigate a connection.'],
            ],
            'sections' => [
                'Added' => [
                    'A step-by-step getting started guide for PPPoE and Hotspot subscribers, with screenshots, Winbox and RouterOS instructions, and connection checks.',
                    'Customer communication campaigns, reusable message templates, configurable SMS gateways, delivery tracking, and email connection testing.',
                    'Notification incident tracking and automation to help teams follow network and service events.',
                    'Detailed IPDR traffic graphs within subscription pages, with subscription ownership history and router analytics.',
                    'Multiple organization assignments for customers and subscriptions, with a primary organization for billing and ownership.',
                    'A Customer Portal setting that lets providers control whether customers can see billing information.',
                    'More precise student searches in the Golestan integration, including additional search criteria and ranges.',
                    'Email verification for new cloud accounts and team notifications when users sign up.',
                ],
                'Changed' => [
                    'User Group access now extends to plans, helping teams see the services relevant to their responsibilities.',
                    'Subscription setup supports customers without an organization and makes saved IP management and address assignments easier to reuse.',
                    'Subscription activity history now includes suspension and activation events.',
                    'Credential viewing now uses a password confirmation dialog, with optional password redaction and stronger credential protection.',
                    'Settings, campaigns, and notification navigation have been reorganized to make common actions easier to find.',
                    'The public website now includes practical ISP guides, product screenshots, and clearer paths to pricing, registration, and guided setup.',
                    'Production releases now use health checks and an atomic switch, with rollback support to improve update reliability.',
                ],
                'Fixed' => [
                    'Corrected usage reconciliation for tenant accounts and legacy NAS accounting records, improving the consistency of reported consumption.',
                    'Resolved subscription editing issues with IP management settings and prefilled address assignments.',
                    'Corrected currency display across billing pages and resolved renewal accounting compatibility issues.',
                    'Fixed SMSPortal v3 response handling so message delivery results are interpreted correctly.',
                    'Improved customer and staff access checks to prevent customer identities from reaching staff-only User Group checks.',
                    'Improved router detail initialization, monitoring archive access, and production logging reliability.',
                    'Public contact submissions now have stronger validation and protection, with notifications delivered in the background.',
                ],
            ],
        ],
        [
            'version' => '0.9.9',
            'date' => 'August 6, 2026',
            'title' => 'Live Bandwidth Insights, Clearer Support, and a Simpler Experience',
            'summary' => 'This release makes daily operations easier with live network traffic visibility, a clearer support workspace, searchable closed tickets, a more focused Customer Portal, and a simpler way for new customers to explore SkyBase.',
            'sections' => [
                'Added' => [
                    'A live network bandwidth view with current download and upload traffic, 24-hour history, peak usage, and the time of the latest sample.',
                    'Router and service-interface bandwidth breakdowns so your team can quickly see where network capacity is being used.',
                    'A searchable Closed tickets view for both staff and customers, while Active tickets remain focused on work that still needs attention.',
                    'A guided setup path on the public website for businesses that want to review their workflow and requirements before getting started.',
                ],
                'Changed' => [
                    'Support conversations now make customer replies, staff replies, internal notes, attachments, ownership, priority, and SLA timing easier to follow.',
                    'Ticket lists now provide clearer search and filtering, active filter summaries, priority controls, and a mobile-friendly layout.',
                    'The public website now presents pricing, product outcomes, founder context, and the next steps in a shorter and clearer journey.',
                    'Customer subscription pages now focus on useful service, plan, usage, billing, and support information without exposing internal operational details.',
                    'Bandwidth monitoring now refreshes in place and reports current router and interface activity without requiring a full page reload.',
                ],
                'Fixed' => [
                    'Closed tickets no longer remain mixed into active support queues and can still be found whenever their history is needed.',
                    'Replying publicly to a closed ticket now reopens it correctly and returns it to active support work.',
                    'Bandwidth pages now handle missing, delayed, or unavailable historical samples without showing misleading values or interrupting the page.',
                    'Ticket reply forms now stay in the normal page flow instead of covering the conversation on smaller screens.',
                ],
            ],
        ],
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
    @php
        $latest = $releases[0];
        $archive = array_slice($releases, 1);
        $years = collect($archive)->map(fn ($release) => date('Y', strtotime($release['date'])))->unique()->mapWithKeys(fn ($year) => [$year => $year])->all();
    @endphp

    <main x-data="changelogArchive">
        <header class="border-b border-[#17211f]/10 bg-[#f7f3ea]">
            <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 sm:py-12">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#145a5a]">Product updates</p>
                <h1 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">What’s new in SkyBase</h1>
                <p class="mt-4 text-base leading-7 text-[#52605d]">A better start. Easier daily work. See what’s changed for you.</p>
                <p class="mt-5 flex flex-wrap gap-x-4 gap-y-2 text-sm text-[#52605d]">
                    <span class="font-semibold text-[#145a5a]">Current version {{ $latest['version'] }}</span>
                    <span>Updated <time datetime="{{ date('Y-m-d', strtotime($latest['date'])) }}">{{ $latest['date'] }}</time></span>
                </p>
            </div>
        </header>

        <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 sm:py-12">
            <article id="v{{ str_replace('.', '-', $latest['version']) }}" class="scroll-mt-28">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-[#145a5a]">Latest release · v{{ $latest['version'] }}</p>
                    <a href="#v{{ str_replace('.', '-', $latest['version']) }}" aria-label="Permanent link to version {{ $latest['version'] }}" class="rounded px-2 py-2 text-sm text-[#52605d] underline underline-offset-4 hover:text-[#145a5a] focus:outline-none focus:ring-2 focus:ring-[#145a5a]">Release link</a>
                </div>
                <h2 class="mt-3 text-3xl font-bold tracking-tight">{{ $latest['title'] }}</h2>
                <p class="mt-4 max-w-2xl text-base leading-7 text-[#52605d]">{{ preg_split('/(?<=[.!?])\s+/', $latest['summary'])[0] }}</p>

                <div class="mt-8 divide-y divide-[#17211f]/10">
                    @foreach($latest['highlights'] ?? [] as $highlight)
                        <section class="grid gap-2 py-5 sm:grid-cols-[0.9fr_1.1fr] sm:gap-8">
                            <h3 class="text-lg font-bold">{{ $highlight['title'] }}</h3>
                            <div>
                                <p class="text-sm leading-7 text-[#52605d]">{{ $highlight['copy'] }}</p>
                                @if(isset($highlight['route']))
                                    <a href="{{ route($highlight['route']) }}" class="mt-2 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-[#145a5a] underline underline-offset-4 focus:outline-none focus:ring-2 focus:ring-[#145a5a]">{{ $highlight['link'] }} <span aria-hidden="true">→</span></a>
                                @endif
                            </div>
                        </section>
                    @endforeach
                </div>

                <details class="mt-5 rounded-xl border border-[#17211f]/15 bg-white p-5 sm:p-6">
                    <summary class="cursor-pointer rounded py-1 font-semibold text-[#145a5a] focus:outline-none focus:ring-2 focus:ring-[#145a5a]">View all changes in v{{ $latest['version'] }}</summary>
                    <div class="mt-5"><x-release-notes :sections="$latest['sections']" /></div>
                </details>
            </article>

            <section aria-labelledby="archive-heading" class="mt-12 border-t border-[#17211f]/15 pt-10">
                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                    <div>
                        <h2 id="archive-heading" class="text-2xl font-bold tracking-tight">Earlier updates</h2>
                        <p class="mt-2 text-sm leading-6 text-[#52605d]">Open a release to read the full notes.</p>
                    </div>
                    <div x-cloak class="w-full sm:w-40">
                        <x-ui.input.select name="release_year" label="Year" :options="$years" placeholder="All years" x-model="year" x-on:change="visibleCount = 5" />
                    </div>
                </div>
                <p x-cloak aria-live="polite" aria-atomic="true" class="mt-5 text-xs text-[#52605d]" x-text="'Showing ' + visibleIds.length + ' of ' + matchingRows.length + ' earlier releases'"></p>

                <div class="mt-4 divide-y divide-[#17211f]/10">
                    @foreach($archive as $release)
                        @php
                            $releaseId = 'v'.str_replace('.', '-', $release['version']);
                            $releaseYear = date('Y', strtotime($release['date']));
                        @endphp
                        <article id="{{ $releaseId }}" data-release-year="{{ $releaseYear }}" x-show="visibleIds.includes('{{ $releaseId }}')" class="scroll-mt-28 py-5">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-[#52605d]">
                                <p class="flex items-center gap-3"><span class="font-bold text-[#145a5a]">v{{ $release['version'] }}</span> <time datetime="{{ date('Y-m-d', strtotime($release['date'])) }}">{{ $release['date'] }}</time></p>
                                <a href="#{{ $releaseId }}" aria-label="Permanent link to version {{ $release['version'] }}" class="rounded px-2 py-2 underline underline-offset-4 hover:text-[#145a5a] focus:outline-none focus:ring-2 focus:ring-[#145a5a]">Release link</a>
                            </div>
                            <details class="mt-1">
                                <summary class="cursor-pointer rounded py-2 focus:outline-none focus:ring-2 focus:ring-[#145a5a]">
                                    <h3 class="inline text-lg font-bold">{{ $release['title'] }}</h3>
                                    <span class="mt-2 block text-sm font-normal leading-7 text-[#52605d]">{{ preg_split('/(?<=[.!?])\s+/', $release['summary'])[0] }}</span>
                                </summary>
                                <div class="mt-4"><x-release-notes :sections="$release['sections']" /></div>
                            </details>
                        </article>
                    @endforeach
                </div>
                <button x-cloak x-show="visibleCount < matchingRows.length" x-on:click="visibleCount += 5" type="button" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl border border-[#145a5a]/30 px-5 py-3 text-sm font-semibold text-[#145a5a] hover:bg-[#f7f3ea] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2">Load older updates</button>
                <noscript><p class="mt-5 text-sm text-[#52605d]">All releases are listed above. Open any release to read its notes.</p></noscript>
            </section>
        </div>
    </main>
@endsection
