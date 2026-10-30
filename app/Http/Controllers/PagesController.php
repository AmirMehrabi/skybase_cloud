<?php

namespace App\Http\Controllers;

use App\Support\Rbac\PermissionRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class PagesController extends Controller
{
    public function guide(Request $request): View
    {
        $serviceType = $request->query('service') === 'hotspot' ? 'hotspot' : 'pppoe';

        return view('modules.guide.index', compact('serviceType'));
    }

    public function index(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }

        if (Auth::check()) {
            $landingRoute = PermissionRegistry::firstAccessibleRoute(Auth::user()) ?? 'dashboard';

            return redirect()->route($landingRoute);
        }

        if (! config('app.cloud.enabled')) {
            return redirect()->route($this->guestEntryRouteName());
        }

        return view('home');
    }

    public function pricing(): View|RedirectResponse
    {
        if (Auth::check()) {
            $landingRoute = PermissionRegistry::firstAccessibleRoute(Auth::user()) ?? 'dashboard';

            return redirect()->route($landingRoute);
        }

        return view('pricing');
    }

    public function features(): View|RedirectResponse
    {
        if (Auth::check()) {
            $landingRoute = PermissionRegistry::firstAccessibleRoute(Auth::user()) ?? 'dashboard';

            return redirect()->route($landingRoute);
        }

        return view('features');
    }

    public function wispManagementSoftware(): View|RedirectResponse
    {
        return $this->searchLandingPage('wisp-management-software');
    }

    public function wispCrm(): View|RedirectResponse
    {
        return $this->searchLandingPage('wisp-crm');
    }

    public function mikrotikIspSoftware(): View|RedirectResponse
    {
        return $this->searchLandingPage('mikrotik-isp-software');
    }

    public function ispCrm(): View|RedirectResponse
    {
        return $this->searchLandingPage('isp-crm');
    }

    public function ispBillingSoftware(): View|RedirectResponse
    {
        return $this->searchLandingPage('isp-billing-software');
    }

    public function choosingIspCrm(): View|RedirectResponse
    {
        return $this->searchLandingPage('choosing-isp-crm');
    }

    public function ispSoftwareMigration(): View|RedirectResponse
    {
        return $this->searchLandingPage('isp-software-migration');
    }

    public function mikrotikBillingGuide(): View|RedirectResponse
    {
        return $this->searchLandingPage('mikrotik-billing-guide');
    }

    public function powercodeAlternative(): View|RedirectResponse
    {
        return $this->alternativePage('powercode');
    }

    public function splynxAlternative(): View|RedirectResponse
    {
        return $this->alternativePage('splynx');
    }

    public function sonarAlternative(): View|RedirectResponse
    {
        return $this->alternativePage('sonar');
    }

    public function changelog(): View
    {
        return view('changelog');
    }

    public function governmentBrochure(): View
    {
        return view('brochures.government-fa');
    }

    public function contact(): View|RedirectResponse
    {
        if (Auth::check()) {
            $landingRoute = PermissionRegistry::firstAccessibleRoute(Auth::user()) ?? 'dashboard';

            return redirect()->route($landingRoute);
        }

        return view('contact');
    }

    private function guestEntryRouteName(): string
    {
        return config('app.cloud.guest_entry') === 'customer' && Route::has('customer.login')
            ? 'customer.login'
            : 'auth.login';
    }

    private function searchLandingPage(string $slug): View|RedirectResponse
    {
        if (Auth::check()) {
            $landingRoute = PermissionRegistry::firstAccessibleRoute(Auth::user()) ?? 'dashboard';

            return redirect()->route($landingRoute);
        }

        $pages = [
            'wisp-management-software' => [
                'route' => 'seo.wisp-management-software',
                'title' => 'WISP Management Software for MikroTik ISPs | SkyBase',
                'meta_description' => 'Manage subscribers, billing, MikroTik routers, RADIUS, support, and network operations with straightforward cloud WISP management software.',
                'eyebrow' => 'WISP management software',
                'headline' => 'Your WISP has enough moving parts. Keep the work together.',
                'intro' => 'A new installation. An overdue invoice. A customer who needs help getting back online. SkyBase keeps customers, billing, MikroTik services, and support together, so the next job is easier to pick up.',
                'summary_title' => 'Less time piecing things together.',
                'summary' => 'General-purpose CRMs understand contacts but not subscriber services, routers, sessions, IP pools, or service status. SkyBase connects the customer and network sides of the business in one operating workflow.',
                'highlights' => [
                    ['title' => 'Subscriber operations', 'copy' => 'Keep customers, plans, subscriptions, service status, usage, and account history connected.'],
                    ['title' => 'Billing and collections', 'copy' => 'Generate invoices, record payments, follow overdue balances, and coordinate service status from the same workspace.'],
                    ['title' => 'Network visibility', 'copy' => 'Track MikroTik routers, sites, sessions, bandwidth, alerts, and IP pool utilization without maintaining separate records.'],
                    ['title' => 'Support and field work', 'copy' => 'Manage tickets, teams, appointments, work orders, materials, and customer follow-up with operational context attached.'],
                ],
                'workflow_title' => 'From new subscriber to ongoing service',
                'workflow' => [
                    ['number' => '01', 'title' => 'Create the customer', 'copy' => 'Capture the customer, service location, plan, billing details, and responsible team.'],
                    ['number' => '02', 'title' => 'Provision the service', 'copy' => 'Assign network settings and activate the subscriber through the appropriate MikroTik and RADIUS workflow.'],
                    ['number' => '03', 'title' => 'Operate from one record', 'copy' => 'Follow connection status, invoices, payments, usage, tickets, and service changes over time.'],
                ],
                'faqs' => [
                    ['question' => 'What is WISP management software?', 'answer' => 'WISP management software combines subscriber, billing, network, support, and operational workflows for wireless internet service providers. It gives commercial and technical teams a shared view of each customer and service.'],
                    ['question' => 'Is SkyBase only for wireless networks?', 'answer' => 'No. SkyBase is designed for small and growing internet providers using MikroTik infrastructure, including WISPs and providers delivering fiber or mixed access services.'],
                    ['question' => 'Can a small WISP start for free?', 'answer' => 'Yes. The SkyBase Free plan supports up to 40 subscribers, with paid tiers available as the subscriber base grows.'],
                ],
            ],
            'wisp-crm' => [
                'route' => 'seo.wisp-crm',
                'title' => 'WISP CRM & Subscriber Management Software | SkyBase',
                'meta_description' => 'Keep WISP customers, subscriptions, billing history, support, usage, and service status together in a CRM designed for internet providers.',
                'eyebrow' => 'WISP CRM',
                'headline' => 'The customer is on the phone. You have the whole story.',
                'intro' => 'A customer calls about their connection. Is the service active? Is an invoice unpaid? Has someone already opened a ticket? SkyBase keeps those answers with the customer, so your team can get on with helping.',
                'summary_title' => 'Keep the details. Spare the customer the repetition.',
                'summary' => 'A WISP customer relationship includes a physical location, an active service, network credentials, recurring billing, support history, and often on-site work. SkyBase keeps those relationships visible without forcing teams to reconstruct them from separate tools.',
                'highlights' => [
                    ['title' => 'Complete subscriber profiles', 'copy' => 'See contact details, service locations, plans, subscription state, balances, usage, and history together.'],
                    ['title' => 'Clear team ownership', 'copy' => 'Use roles, groups, assignments, notes, tickets, and work orders to keep responsibility and next actions visible.'],
                    ['title' => 'Billing context', 'copy' => 'Review invoices, payment status, credit, and service information while speaking with the customer.'],
                    ['title' => 'Customer self-service', 'copy' => 'Give subscribers a portal for service details, invoices, notifications, profile updates, and support requests.'],
                ],
                'workflow_title' => 'A customer should not have to explain everything twice.',
                'workflow' => [
                    ['number' => '01', 'title' => 'Sales captures the service', 'copy' => 'Record the customer, location, selected plan, and the information required for activation.'],
                    ['number' => '02', 'title' => 'Operations activates it', 'copy' => 'Connect the subscription to its site, router, credentials, IP assignment, and service state.'],
                    ['number' => '03', 'title' => 'Support sees the full picture', 'copy' => 'Handle questions with billing, connection, usage, ticket, and previous activity context in view.'],
                ],
                'faqs' => [
                    ['question' => 'How is a WISP CRM different from a general CRM?', 'answer' => 'A WISP CRM links the customer relationship to subscriptions, network access, billing cycles, usage, service status, support, and field operations. A general CRM typically stops at contacts and sales activity.'],
                    ['question' => 'Can customers access their own information?', 'answer' => 'Yes. SkyBase includes a customer portal where subscribers can review service information, invoices, notifications, usage, and support requests.'],
                    ['question' => 'Can different teams have different access?', 'answer' => 'Yes. SkyBase includes tenant-level roles, permissions, and user groups so staff access can match their responsibilities.'],
                ],
            ],
            'mikrotik-isp-software' => [
                'route' => 'seo.mikrotik-isp-software',
                'title' => 'MikroTik ISP Software for RADIUS, PPPoE & Billing | SkyBase',
                'meta_description' => 'Connect MikroTik routers, RADIUS, PPPoE subscribers, IP pools, billing, monitoring, and support in cloud ISP management software.',
                'eyebrow' => 'MikroTik ISP software',
                'headline' => 'Your MikroTik network. Your customers. Finally on the same page.',
                'intro' => 'A session tells you someone is connected. A customer record tells you who they are and what they need. SkyBase brings both together: MikroTik routers, RADIUS access, subscriptions, billing, and support.',
                'summary_title' => 'Know which service you are looking after.',
                'summary' => 'Network tools can show sessions and device state but rarely explain the customer, plan, invoice, or support issue behind them. SkyBase connects those records so teams can understand what changed and take the appropriate action.',
                'highlights' => [
                    ['title' => 'MikroTik and RADIUS', 'copy' => 'Connect routers, manage subscriber authentication, inspect active sessions, and keep service credentials associated with subscriptions.'],
                    ['title' => 'PPPoE and Hotspot workflows', 'copy' => 'Support common MikroTik access methods while keeping profiles, bandwidth settings, and service state visible.'],
                    ['title' => 'IPAM and routed services', 'copy' => 'Manage IP pools, assignments, utilization, and subscription routes alongside the customers using them.'],
                    ['title' => 'Monitoring and usage', 'copy' => 'Review router status, connection state, bandwidth history, usage, and operational alerts from the cloud dashboard.'],
                ],
                'workflow_title' => 'A practical MikroTik subscriber lifecycle',
                'workflow' => [
                    ['number' => '01', 'title' => 'Connect the infrastructure', 'copy' => 'Register sites and routers, verify connectivity, and prepare the profiles and IP resources used by services.'],
                    ['number' => '02', 'title' => 'Assign network access', 'copy' => 'Link the customer subscription to its router, RADIUS credentials, bandwidth profile, and IP configuration.'],
                    ['number' => '03', 'title' => 'Monitor and maintain', 'copy' => 'Follow sessions, connection status, usage, router health, billing events, and support activity as service continues.'],
                ],
                'faqs' => [
                    ['question' => 'Does SkyBase work with MikroTik RouterOS?', 'answer' => 'Yes. SkyBase is built around MikroTik ISP workflows, including router connectivity, RADIUS-backed access, PPPoE and Hotspot services, profiles, sessions, and IP resources.'],
                    ['question' => 'Can SkyBase manage RADIUS subscribers?', 'answer' => 'Yes. Subscriber access and RADIUS records are connected to customer subscriptions so credentials, service state, sessions, and operational history remain related.'],
                    ['question' => 'Can I see bandwidth and connection status?', 'answer' => 'Yes. SkyBase includes router and subscription monitoring, connection status, session visibility, bandwidth history, and customer usage views.'],
                ],
            ],
        ];

        $pages += [
            'isp-crm' => [
                'route' => 'seo.isp-crm',
                'title' => 'ISP CRM Software for Customers, Billing & Support | SkyBase',
                'meta_description' => 'An ISP CRM that keeps customers, subscriptions, invoices, support, and MikroTik services together. See the product and start free with up to 40 subscribers.',
                'eyebrow' => 'ISP CRM software',
                'headline' => 'One place to pick up where the last conversation left off.',
                'intro' => 'Your billing team knows the balance. Your technicians know the connection. Support needs both. SkyBase gives your internet service provider a shared customer record, with the service and the work attached.',
                'summary_title' => 'A contact is only the beginning.',
                'summary' => 'For an ISP, a customer also means subscriptions, service locations, network access, invoices, and support history. SkyBase keeps these connected. Your team can answer a question without asking three colleagues to reconstruct the account.',
                'highlights' => [
                    ['title' => 'Customers and organizations', 'copy' => 'Keep individual subscribers and business accounts organized, with contact and service details close at hand.'],
                    ['title' => 'Services and subscriptions', 'copy' => 'See the plan, service state, network assignment, and usage behind an account.'],
                    ['title' => 'Invoices and payments', 'copy' => 'Look up what was billed, what was paid, and what still needs attention.'],
                    ['title' => 'Support and handovers', 'copy' => 'Keep tickets, notes, and work orders with the customer so the next person has somewhere to start.'],
                ],
                'workflow_title' => 'From the first call to the next renewal.',
                'workflow' => [
                    ['number' => '01', 'title' => 'Record the agreement', 'copy' => 'Create the customer and subscription with the selected plan and billing details.'],
                    ['number' => '02', 'title' => 'Connect the service', 'copy' => 'Associate network access and the router with the subscription. Check the setup before activation.'],
                    ['number' => '03', 'title' => 'Keep the history', 'copy' => 'Follow invoices, payments, support requests, and service changes from the same account.'],
                ],
                'faqs' => [
                    ['question' => 'What is an ISP CRM?', 'answer' => 'An ISP CRM manages customer relationships alongside internet services. It connects account details to subscriptions, billing, network access, and support, giving staff the context to handle a customer request.'],
                    ['question' => 'Can one account have more than one service?', 'answer' => 'Yes. Customer accounts can have multiple subscriptions, each with its own plan and service details. Staff can open the relevant subscription from the customer workspace.'],
                    ['question' => 'Is SkyBase suitable for every ISP network?', 'answer' => 'SkyBase is focused on MikroTik-based operations. If your network depends on particular OLTs, voice billing, accounting software, or other systems, bring that list to a guided setup so we can check the fit.'],
                ],
            ],
            'isp-billing-software' => [
                'route' => 'seo.isp-billing-software',
                'title' => 'ISP Billing Software for Recurring Invoices & Payments | SkyBase',
                'meta_description' => 'Keep ISP invoices, payments, balances, and subscription billing together. Explore recurring billing and overdue service controls for MikroTik ISPs and WISPs.',
                'eyebrow' => 'ISP billing software',
                'headline' => 'Billing day should feel like an ordinary day.',
                'intro' => 'Keep invoices, payments, and subscription billing in one place. SkyBase helps your ISP see what is due, record what has been paid, and follow up with the right customer.',
                'summary_title' => 'The invoice belongs with the service.',
                'summary' => 'A balance means more when you can see the subscription behind it. SkyBase connects billing to the customer and service, including billing dates, grace periods, and configured suspension controls. Your team can check the facts before taking action.',
                'highlights' => [
                    ['title' => 'Recurring invoices', 'copy' => 'Generate recurring invoices and review issue dates, due dates, and invoice status.'],
                    ['title' => 'Payment records', 'copy' => 'Record payments against invoices and keep outstanding balances visible.'],
                    ['title' => 'Service controls', 'copy' => 'Configure billing and automatic suspension per subscription, with a grace period for overdue invoices.'],
                    ['title' => 'Customer access', 'copy' => 'Let subscribers review their invoices and service information in the customer portal.'],
                ],
                'workflow_title' => 'A clearer path from invoice to payment.',
                'workflow' => [
                    ['number' => '01', 'title' => 'Set the billing details', 'copy' => 'Check the subscription price, billing cycle, next billing date, and overdue policy.'],
                    ['number' => '02', 'title' => 'Review the invoices', 'copy' => 'Filter by status and date to see issued, paid, partially paid, and overdue invoices.'],
                    ['number' => '03', 'title' => 'Follow up with context', 'copy' => 'Open the customer and service record before resolving a balance or changing service status.'],
                ],
                'faqs' => [
                    ['question' => 'Does SkyBase support recurring ISP billing?', 'answer' => 'Yes. Subscriptions have billing cycles and next billing dates, and the invoice workspace supports recurring invoice generation.'],
                    ['question' => 'Can an unpaid invoice lead to service suspension?', 'answer' => 'SkyBase has per-subscription automatic suspension settings and grace days. Check those settings and test the router workflow before using automatic suspension on live services.'],
                    ['question' => 'Will SkyBase process payments through my existing gateway?', 'answer' => 'Recording a payment and processing a payment are different jobs. Tell us your gateway, country, currency, and accounting requirements so we can confirm the supported workflow before you switch.'],
                ],
            ],
            'choosing-isp-crm' => [
                'route' => 'guides.choosing-isp-crm',
                'title' => 'How to Choose an ISP CRM: A Practical Checklist | SkyBase',
                'meta_description' => 'Evaluate an ISP CRM with real customer, billing, provisioning, and support tasks. A practical checklist for small ISPs comparing software.',
                'eyebrow' => 'Guide · Choosing an ISP CRM',
                'headline' => 'Bring a normal working day to the demo.',
                'intro' => 'A feature list can make every ISP CRM look much the same. Bring five jobs your team does every week. Ask to see each one completed, from beginning to end.',
                'summary_title' => 'Start with the work that keeps getting stuck.',
                'summary' => 'Ask billing, support, and network staff where they lose time. Choose a new subscriber, an overdue account, a connection fault, a plan change, and a ticket handover. Use sample data to compare the same jobs in every product. Write down what needs a second tool or a manual step.',
                'highlights' => [
                    ['title' => 'Find the account', 'copy' => 'Can support see the customer, subscriptions, balance, and ticket history without asking another team?'],
                    ['title' => 'Check the network fit', 'copy' => 'List your routers, access methods, IP requirements, and integrations. Ask for a demonstration of the ones you depend on.'],
                    ['title' => 'Check the bill', 'copy' => 'Compare costs at your current size and your next growth step, including hosting, add-ons, setup, and support.'],
                    ['title' => 'Check the way out', 'copy' => 'Ask which records you can export, in what format, and what happens to your data when you leave.'],
                ],
                'workflow_title' => 'A small evaluation beats a big promise.',
                'workflow' => [
                    ['number' => '01', 'title' => 'Write down five jobs', 'copy' => 'Choose real tasks and describe what a successful result looks like. Keep the same checklist for each vendor.'],
                    ['number' => '02', 'title' => 'Try them with your team', 'copy' => 'Use a test account. Include the person who will do the work every day, not just the person buying the software.'],
                    ['number' => '03', 'title' => 'Record the gaps', 'copy' => 'Separate what works today from what needs custom work or is only planned. Agree on any essential gaps before committing.'],
                ],
                'faqs' => [
                    ['question' => 'Should a small ISP use a general CRM?', 'answer' => 'A general CRM can suit sales and contact management. If you also need subscriber services, billing, and network access, evaluate the integrations and manual work needed to keep those records consistent.'],
                    ['question' => 'What should I ask about pricing?', 'answer' => 'Ask how subscribers are counted, whether there is a minimum charge, which integrations cost extra, and whether support, hosting, and migration are included. Compare the same subscriber count and requirements.'],
                    ['question' => 'How should I evaluate SkyBase?', 'answer' => 'Bring those five jobs and your network requirements to a guided setup. SkyBase focuses on MikroTik ISPs; the useful outcome is knowing whether your own workflow fits.'],
                ],
            ],
            'isp-software-migration' => [
                'route' => 'guides.isp-software-migration',
                'title' => 'ISP Software Migration Checklist: Customers, Billing & Access | SkyBase',
                'meta_description' => 'Plan an ISP software migration around customer records, opening balances, network access, testing, and a rollback plan before changing live services.',
                'eyebrow' => 'Guide · ISP software migration',
                'headline' => 'Changing software is a project. Give it a plan.',
                'intro' => 'Your customers still need their connection while you change the system behind it. Start with a small test, clear responsibilities, and a way back if something does not work.',
                'summary_title' => 'Agree on what moves before moving anything.',
                'summary' => 'List the records you need: customers, contacts, subscriptions, plans, balances, invoices, tickets, and network assignments. Ask both vendors what can be exported and imported. Treat payment tokens and access credentials separately; their portability must be confirmed, not assumed.',
                'highlights' => [
                    ['title' => 'Customer records', 'copy' => 'Map identifiers, contacts, service locations, status values, and the relationships between accounts and subscriptions.'],
                    ['title' => 'Billing cutover', 'copy' => 'Reconcile balances and unpaid invoices. Choose which system issues the next bill to avoid billing the same service twice.'],
                    ['title' => 'Network access', 'copy' => 'Test authentication, profiles, IP assignments, and service controls on a non-production account before a live cutover.'],
                    ['title' => 'Recovery and ownership', 'copy' => 'Keep backups, a rollback decision point, named owners, and a list of checks for the morning after the switch.'],
                ],
                'workflow_title' => 'Prove a small move before a large one.',
                'workflow' => [
                    ['number' => '01', 'title' => 'Map a sample export', 'copy' => 'Use a small, authorized sample to check field mappings and record relationships. Transfer it through an agreed secure channel.'],
                    ['number' => '02', 'title' => 'Reconcile and test', 'copy' => 'Compare record counts and balances. Test activation, authentication, invoice generation, and staff access.'],
                    ['number' => '03', 'title' => 'Schedule the cutover', 'copy' => 'Agree on a change window, staff coverage, customer communications, and rollback criteria. Verify services and billing afterward.'],
                ],
                'faqs' => [
                    ['question' => 'Can I migrate from Splynx, Sonar, or Powercode to SkyBase?', 'answer' => 'We need to review your exports, network dependencies, and required records first. A comparison page is not a guarantee that every field, payment token, historical record, or integration can be transferred.'],
                    ['question' => 'Can you promise zero downtime?', 'answer' => 'A reliable estimate needs a tested plan for your network. Agree on the expected interruption, validation checks, and rollback procedure before changing live services.'],
                    ['question' => 'Should I email a full customer database for a quote?', 'answer' => 'Start with record counts and a list of fields and integrations. Arrange an appropriate secure transfer only when a sample is needed for an agreed migration review.'],
                ],
            ],
            'mikrotik-billing-guide' => [
                'route' => 'guides.mikrotik-billing',
                'title' => 'MikroTik PPPoE Billing: What to Check Before Automating | SkyBase',
                'meta_description' => 'Connect MikroTik PPPoE services to customer billing with a practical checklist for RADIUS, invoice dates, overdue policies, and service testing.',
                'eyebrow' => 'Guide · MikroTik billing',
                'headline' => 'An unpaid invoice and a broken connection need different answers.',
                'intro' => 'Before automating MikroTik billing, make sure your team can tell a billing restriction from a network fault. The customer record, invoice, and subscriber session all matter.',
                'summary_title' => 'Make the service and billing rules agree.',
                'summary' => 'Map each customer subscription to the correct access method, router, credentials, and billing cycle. Decide when invoices are issued, how much time customers have to pay, and who handles exceptions. Test the policy with a dedicated account before applying it to subscribers.',
                'highlights' => [
                    ['title' => 'Authentication', 'copy' => 'Confirm how the subscriber authenticates and where service state is enforced. Test the RADIUS and router path you actually use.'],
                    ['title' => 'Billing dates', 'copy' => 'Check invoice issue dates, due dates, renewal dates, and grace periods against your customer agreement.'],
                    ['title' => 'Overdue handling', 'copy' => 'Decide who may suspend service and which accounts need an exception. Check the configured controls before enabling automation.'],
                    ['title' => 'Support checks', 'copy' => 'Give support a way to inspect invoice status and subscription state before treating every disconnection as a payment issue.'],
                ],
                'workflow_title' => 'Test the whole billing cycle.',
                'workflow' => [
                    ['number' => '01', 'title' => 'Create a test service', 'copy' => 'Use a dedicated customer and subscription with the intended router and access method.'],
                    ['number' => '02', 'title' => 'Walk through an invoice', 'copy' => 'Check generation, due dates, payment recording, and how any overdue restriction reaches the service.'],
                    ['number' => '03', 'title' => 'Check recovery', 'copy' => 'Verify the steps to restore access after payment and document who handles failures or exceptions.'],
                ],
                'faqs' => [
                    ['question' => 'Is RADIUS itself an ISP billing system?', 'answer' => 'RADIUS handles network authentication, authorization, and accounting. A billing application handles commercial records such as plans, invoices, and payments. The integration needs to keep service decisions consistent with those records.'],
                    ['question' => 'Does every disconnected session mean the customer is overdue?', 'answer' => 'No. A connection can fail for many reasons. Check billing status alongside subscription state, router health, and authentication information before deciding what to do.'],
                    ['question' => 'Where can I see the SkyBase billing workflow?', 'answer' => 'The ISP billing page shows the invoice workspace. A guided setup lets us check your billing rules and MikroTik requirements together.'],
                ],
            ],
        ];

        $screenshots = [
            'wisp-management-software' => ['dashboard', 'Start the day with the work that needs attention.', 'The dashboard brings service status and operational follow-up into view.'],
            'wisp-crm' => ['customer', 'Answer the question while the customer is still on the phone.', 'The customer workspace keeps service access, invoices, payments, tickets, and notes within reach.'],
            'isp-crm' => ['customer', 'Give the next person a useful place to start.', 'A shared customer workspace connects the account to its services and history.'],
            'isp-billing-software' => ['billing', 'See which invoices need a closer look.', 'Filter invoices by status and date, then open the account behind the balance.'],
            'mikrotik-isp-software' => ['subscription', 'The service has a home of its own.', 'Subscription controls keep the plan, billing settings, and IP assignment together.'],
        ];

        if (isset($screenshots[$slug])) {
            [$image, $heading, $caption] = $screenshots[$slug];
            $pages[$slug]['screenshot'] = compact('image', 'heading', 'caption');
        }

        return view('seo.show', [
            'page' => $pages[$slug],
            'relatedPages' => collect($pages)
                ->except($slug)
                ->map(fn (array $page): array => [
                    'route' => $page['route'],
                    'eyebrow' => $page['eyebrow'],
                    'headline' => $page['headline'],
                ]),
        ]);
    }

    private function alternativePage(string $competitor): View|RedirectResponse
    {
        if (Auth::check()) {
            $landingRoute = PermissionRegistry::firstAccessibleRoute(Auth::user()) ?? 'dashboard';

            return redirect()->route($landingRoute);
        }

        $pages = [
            'splynx' => [
                'competitor' => 'Splynx',
                'route' => 'alternatives.splynx',
                'title' => 'Splynx Alternative | SkyBase Cloud for MikroTik ISPs',
                'meta_description' => 'Compare SkyBase and Splynx for MikroTik ISP operations. Review cloud pricing, customer and billing workflows, integrations, and migration checks.',
                'meta_keywords' => 'Splynx alternative, Splynx pricing alternative, ISP billing software, MikroTik ISP management, Splynx competitor',
                'eyebrow' => 'Splynx alternative',
                'headline' => 'Considering a Splynx alternative? Start with your working day.',
                'intro' => 'You need to connect customers, send the right bills, and help people when something goes wrong. SkyBase brings that work together for MikroTik ISPs. Here is what to check when comparing it with Splynx.',
                'best_for_skybase' => 'A small or growing MikroTik ISP looking for customers, billing, network visibility, and support in a shared cloud workspace.',
                'best_for_competitor' => 'An operator that needs the apps, integrations, or paid add-ons in the Splynx ecosystem. Check those requirements before changing platforms.',
                'pricing_note' => 'Splynx lists an entry price of $255/month for up to 400 active subscribers, including its maintenance fee. Some add-ons cost extra.',
                'source_url' => 'https://splynx.com/pricing/',
                'source_label' => 'Splynx pricing page',
                'cards' => [
                    ['label' => 'SkyBase entry', 'value' => '$0/month', 'note' => 'Free up to 40 subscribers'],
                    ['label' => 'Paid SkyBase', 'value' => '$69/month', 'note' => 'Starter plan up to 150 subscribers'],
                    ['label' => 'Try your workflow', 'value' => 'Guided setup', 'note' => 'Bring the jobs your team does every day'],
                ],
                'rows' => [
                    ['feature' => 'Cloud entry', 'skybase' => 'Free for up to 40 subscribers; $69/month up to 150', 'competitor' => '$255/month up to 400 active subscribers'],
                    ['feature' => 'Daily work', 'skybase' => 'Customers, subscriptions, billing, tickets, work orders, and MikroTik operations', 'competitor' => 'Billing, CRM, network operations, field service, and inventory'],
                    ['feature' => 'Apps and integrations', 'skybase' => 'Browser workspace and customer portal; confirm each required integration with us', 'competitor' => 'Mobile apps and public integrations; selected add-ons carry extra charges'],
                    ['feature' => 'Deployment', 'skybase' => 'Managed cloud; separate on-premise plans are listed on our pricing page', 'competitor' => 'Cloud hosting by default; on-premise available'],
                ],
                'faqs' => [
                    ['question' => 'Is SkyBase a Splynx alternative?', 'answer' => 'SkyBase is an option for MikroTik ISPs evaluating customer management, billing, and service operations. Compare your required workflows and integrations before deciding whether to move.'],
                    ['question' => 'What should I compare besides the monthly price?', 'answer' => 'Check your required access methods, payment and accounting integrations, field work, staff permissions, and exports. A lower starting price only helps if the product covers the work you need.'],
                    ['question' => 'Can I migrate from Splynx to SkyBase?', 'answer' => 'Bring a list of required records, integrations, routers, and access methods. We need to review the export and test the mappings before confirming scope, timing, or what can be transferred.'],
                ],
                'price_examples' => [
                    ['subscribers' => 150, 'skybase' => '$69/month', 'competitor' => '$255/month'],
                    ['subscribers' => 300, 'skybase' => '$129/month', 'competitor' => '$255/month'],
                    ['subscribers' => 600, 'skybase' => '$239/month', 'competitor' => '$400/month'],
                ],
                'pricing_basis' => 'Monthly USD examples using the published Splynx license bands and maintenance fees, without annual discounts or optional add-ons. Splynx counts active customers and rounds licenses in steps of 100; SkyBase plan limits use subscriber counts. Confirm how each vendor counts your accounts.',
                'checked_at' => 'October 30, 2026',
            ],
            'sonar' => [
                'competitor' => 'Sonar',
                'route' => 'alternatives.sonar',
                'title' => 'Sonar Alternative | SkyBase Cloud for MikroTik ISPs',
                'meta_description' => 'Compare SkyBase and Sonar for ISP customer management, billing, and support. Review pricing examples and the requirements to check before switching.',
                'meta_keywords' => 'Sonar alternative, Sonar software alternative, ISP billing software, MikroTik ISP management, Sonar competitor',
                'eyebrow' => 'Sonar alternative',
                'headline' => 'Looking for a Sonar alternative? Bring your real requirements.',
                'intro' => 'Changing your ISP software takes care. SkyBase is worth a look if your team runs MikroTik and wants customers, subscriptions, billing, and support together. Compare the work you do today before deciding to move.',
                'best_for_skybase' => 'A MikroTik ISP that wants a managed cloud workspace and published plans starting with a free tier.',
                'best_for_competitor' => 'A broadband provider that relies on Sonar field dispatch, inventory, procurement, reporting, or partner services. Check how you would replace each dependency.',
                'pricing_note' => 'Sonar lists a $500/month starting minimum with a contract, and $1.25 per active subscriber in its first pricing tier. Premium services may add to the quote.',
                'source_url' => 'https://sonar.software/pricing',
                'source_label' => 'Sonar pricing page',
                'cards' => [
                    ['label' => 'SkyBase entry', 'value' => '$0/month', 'note' => 'Free up to 40 subscribers'],
                    ['label' => 'Paid SkyBase', 'value' => '$69/month', 'note' => 'Starter plan up to 150 subscribers'],
                    ['label' => 'Sonar public floor', 'value' => '$500/month', 'note' => 'With contract, as publicly listed'],
                ],
                'rows' => [
                    ['feature' => 'Cloud entry', 'skybase' => 'Free for up to 40 subscribers; $69/month up to 150', 'competitor' => 'Starts at $500/month with contract'],
                    ['feature' => 'Daily work', 'skybase' => 'Customers, billing, tickets, work orders, and MikroTik services', 'competitor' => 'Billing, CRM, dispatch, inventory, procurement, and network tools'],
                    ['feature' => 'Payments and reporting', 'skybase' => 'Invoice and payment records; check your gateway and reporting requirements', 'competitor' => 'Financial reporting and optional services including sonarPay and DataConnect'],
                    ['feature' => 'Before switching', 'skybase' => 'Review your network and required integrations in a guided setup', 'competitor' => 'Check which existing Sonar and partner workflows your team depends on'],
                ],
                'faqs' => [
                    ['question' => 'Is SkyBase a Sonar alternative?', 'answer' => 'SkyBase is an option for MikroTik ISPs evaluating customer management, billing, and service operations. Compare your required workflows and integrations before deciding whether to move.'],
                    ['question' => 'Is SkyBase a replacement for every Sonar feature?', 'answer' => 'No. Check your required integrations, dispatch workflows, reporting, and payment services individually. We will review your requirements before recommending a move.'],
                    ['question' => 'Can my Sonar history and payment setup move with me?', 'answer' => 'That needs a migration review. Export formats, historical records, payment tokens, and integrations need separate checks. We do not promise a complete transfer before testing a sample.'],
                ],
                'price_examples' => [
                    ['subscribers' => 150, 'skybase' => '$69/month', 'competitor' => '$500/month minimum'],
                    ['subscribers' => 300, 'skybase' => '$129/month', 'competitor' => '$500/month minimum'],
                    ['subscribers' => 600, 'skybase' => '$239/month', 'competitor' => '$750/month'],
                ],
                'pricing_basis' => 'Monthly USD examples applying the published Sonar first-tier rate and minimum with an annual contract. Premium services, integrations, and other quoted costs are excluded. Confirm subscriber counting and your final quote with each vendor.',
                'checked_at' => 'October 30, 2026',
            ],
            'powercode' => [
                'competitor' => 'Powercode',
                'route' => 'alternatives.powercode',
                'title' => 'Powercode Alternative for MikroTik ISPs | SkyBase',
                'meta_description' => 'Considering a Powercode alternative? Compare SkyBase cloud ISP workflows with Powercode Command and Link, and plan the checks needed before switching.',
                'meta_keywords' => 'Powercode alternative, Powercode CRM alternative, ISP CRM, WISP billing software',
                'eyebrow' => 'Powercode alternative',
                'headline' => 'A Powercode alternative starts with knowing what you need to keep.',
                'intro' => 'The customer records are one part of the move. Billing, provisioning, and the systems keeping subscribers online matter too. SkyBase is a cloud option for MikroTik ISPs; use this page to work out whether it belongs on your shortlist.',
                'best_for_skybase' => 'A MikroTik operator that wants managed cloud hosting and a shared workspace for customers, subscriptions, billing, and support.',
                'best_for_competitor' => 'An operator that depends on Powercode Command or Link, or wants the on-premise deployment described by Powercode.',
                'pricing_note' => 'Ask Powercode for a quote covering the products and subscriber count you need. We have not established a comparable public monthly total and do not estimate one here.',
                'source_url' => 'https://powercode.com/',
                'source_label' => 'Powercode product overview',
                'cards' => [
                    ['label' => 'SkyBase entry', 'value' => '$0/month', 'note' => 'Free up to 40 subscribers'],
                    ['label' => 'Paid SkyBase', 'value' => '$69/month', 'note' => 'Up to 150 subscribers'],
                    ['label' => 'Powercode cost', 'value' => 'Get a quote', 'note' => 'Confirm the products you need'],
                ],
                'rows' => [
                    ['feature' => 'Business operations', 'skybase' => 'Customers, subscriptions, invoices, payments, tickets, and work orders', 'competitor' => 'Command covers billing, accounts, provisioning, tickets, and scheduling'],
                    ['feature' => 'Network dependencies', 'skybase' => 'MikroTik, RADIUS, subscription access, and IP assignments', 'competitor' => 'Link provides DHCP and monitoring alongside Command'],
                    ['feature' => 'Deployment', 'skybase' => 'Managed cloud, with separate on-premise plans available', 'competitor' => 'On-premise on operator-owned hardware'],
                    ['feature' => 'Migration check', 'skybase' => 'Test your required workflows before agreeing on a cutover', 'competitor' => 'Identify dependencies on Command, Link, and any VMU/BMU installation'],
                ],
                'faqs' => [
                    ['question' => 'Is SkyBase a Powercode alternative?', 'answer' => 'It is an option to evaluate for MikroTik ISP customer management, billing, and service operations. Whether it can replace your setup depends on the Powercode products and integrations you use.'],
                    ['question' => 'Will SkyBase replace Powercode Link or a BMU directly?', 'answer' => 'Do not assume a direct replacement. List the DHCP, monitoring, routing, and service enforcement jobs in your network and test the proposed design before changing them.'],
                    ['question' => 'Can you import my Powercode data?', 'answer' => 'We need to review available exports and required fields first. Start with record counts and a list of dependencies, then agree on a sample import and reconciliation checks.'],
                ],
                'checked_at' => 'October 30, 2026',
            ],
        ];

        return view('alternatives.show', [
            'page' => $pages[$competitor],
        ]);
    }
}
