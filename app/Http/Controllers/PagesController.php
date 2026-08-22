<?php

namespace App\Http\Controllers;

use App\Support\Rbac\PermissionRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class PagesController extends Controller
{
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
                'headline' => 'One cloud workspace for running a growing WISP.',
                'intro' => 'SkyBase brings customer records, service plans, MikroTik operations, billing follow-up, support, and field work together so your team can act without jumping between disconnected systems.',
                'summary_title' => 'Built around the daily work of an internet provider',
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
                'headline' => 'A customer record that understands the internet service behind it.',
                'intro' => 'SkyBase connects each customer to subscriptions, plans, invoices, payments, network access, usage, tickets, and field work—giving every team the context needed to resolve the next task.',
                'summary_title' => 'More than a contact database',
                'summary' => 'A WISP customer relationship includes a physical location, an active service, network credentials, recurring billing, support history, and often on-site work. SkyBase keeps those relationships visible without forcing teams to reconstruct them from separate tools.',
                'highlights' => [
                    ['title' => 'Complete subscriber profiles', 'copy' => 'See contact details, service locations, plans, subscription state, balances, usage, and history together.'],
                    ['title' => 'Clear team ownership', 'copy' => 'Use roles, groups, assignments, notes, tickets, and work orders to keep responsibility and next actions visible.'],
                    ['title' => 'Billing context', 'copy' => 'Review invoices, payment status, credit, and service information while speaking with the customer.'],
                    ['title' => 'Customer self-service', 'copy' => 'Give subscribers a portal for service details, invoices, notifications, profile updates, and support requests.'],
                ],
                'workflow_title' => 'One subscriber story across every team',
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
                'headline' => 'Connect subscriber operations to your MikroTik network.',
                'intro' => 'SkyBase gives MikroTik-based ISPs one place to manage customers, subscriptions, RADIUS access, PPPoE services, router health, IP resources, billing, and support workflows.',
                'summary_title' => 'Operational context on both sides of the router',
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
                'meta_description' => 'Compare SkyBase with Splynx for MikroTik ISP management. See pricing fit, cloud Radius, customer workflows, router visibility, and WhatsApp onboarding.',
                'meta_keywords' => 'Splynx alternative, Splynx pricing alternative, ISP billing software, MikroTik ISP management, Splynx competitor',
                'eyebrow' => 'Splynx alternative',
                'headline' => 'SkyBase vs Splynx: a simpler alternative for MikroTik ISPs.',
                'intro' => 'Splynx is a broad ISP framework for teams that need a deep OSS/BSS platform. SkyBase is built for small and growing MikroTik operators that want cloud Radius, customer workflows, billing follow-up, router visibility, and direct onboarding without starting with enterprise complexity.',
                'best_for_skybase' => 'Small and growing MikroTik ISPs that want a practical cloud dashboard and clear monthly tiers.',
                'best_for_competitor' => 'Operators that need a wide ISP framework with extensive add-ons, integrations, and implementation support.',
                'pricing_note' => 'Splynx publicly describes subscription pricing based on active customers, calculated in 100-subscriber intervals with an entry license up to 400 subscribers.',
                'source_url' => 'https://splynx.com/pricing/',
                'source_label' => 'Splynx pricing page',
                'cards' => [
                    ['label' => 'SkyBase entry', 'value' => '$0/month', 'note' => 'Free up to 40 subscribers'],
                    ['label' => 'Paid SkyBase', 'value' => '$69/month', 'note' => 'Starter plan up to 150 subscribers'],
                    ['label' => 'Support path', 'value' => 'WhatsApp', 'note' => 'Direct setup and onboarding help'],
                ],
                'rows' => [
                    ['feature' => 'Primary fit', 'skybase' => 'MikroTik-first cloud ISP operations', 'competitor' => 'Broad OSS/BSS framework'],
                    ['feature' => 'Starting point', 'skybase' => 'Free plan up to 40 subscribers', 'competitor' => 'Entry license up to 400 active subscribers'],
                    ['feature' => 'Pricing shape', 'skybase' => 'Simple published tiers by subscriber count', 'competitor' => 'Subscription pricing by active customer count'],
                    ['feature' => 'Hosting', 'skybase' => 'Cloud hosting included', 'competitor' => 'Cloud default; on-premise possible'],
                    ['feature' => 'MikroTik workflow', 'skybase' => 'Focused PPPoE, Hotspot, Radius, router, and customer flows', 'competitor' => 'Wider network and business operations toolkit'],
                    ['feature' => 'Best decision', 'skybase' => 'Choose SkyBase when speed, price clarity, and MikroTik focus matter most', 'competitor' => 'Choose Splynx when you need a larger framework and deeper add-on ecosystem'],
                ],
                'faqs' => [
                    ['question' => 'Is SkyBase a Splynx alternative?', 'answer' => 'Yes, for MikroTik ISPs that want a simpler cloud system for customers, Radius, routers, billing follow-up, and support workflows. Splynx may fit better when you need a larger OSS/BSS framework with extensive add-ons.'],
                    ['question' => 'Why would an ISP choose SkyBase instead of Splynx?', 'answer' => 'SkyBase is easier to evaluate for small teams because the public cloud tiers start free and move into clear monthly pricing. The product focus is narrower: MikroTik operations, Radius access, customer activation, and practical onboarding.'],
                    ['question' => 'Can I migrate from Splynx to SkyBase?', 'answer' => 'Yes. During a demo we review your customer count, plans, routers, Radius setup, and billing process so we can map the simplest migration path before you switch.'],
                ],
            ],
            'sonar' => [
                'competitor' => 'Sonar',
                'route' => 'alternatives.sonar',
                'title' => 'Sonar Alternative | SkyBase Cloud for MikroTik ISPs',
                'meta_description' => 'Compare SkyBase with Sonar for MikroTik ISP management. See small-ISP pricing fit, cloud Radius, router visibility, customer workflows, and WhatsApp support.',
                'meta_keywords' => 'Sonar alternative, Sonar software alternative, ISP billing software, MikroTik ISP management, Sonar competitor',
                'eyebrow' => 'Sonar alternative',
                'headline' => 'SkyBase vs Sonar: an affordable alternative for MikroTik ISPs.',
                'intro' => 'Sonar is a mature platform for broadband providers that need a wide operations suite. SkyBase is built for MikroTik-based ISPs that want the essentials in a cloud dashboard: Radius, customer activation, subscriptions, router visibility, billing follow-up, and support without a high starting commitment.',
                'best_for_skybase' => 'MikroTik ISPs that want a low-friction cloud start, clear small-ISP pricing, and direct onboarding.',
                'best_for_competitor' => 'Larger broadband teams that need broader field service, inventory, reporting, and enterprise operations features.',
                'pricing_note' => 'Sonar publicly lists active-subscriber pricing starting at $500/month with contract, with tiers beginning at $1.25 per subscriber for 1 to 5,000 subscribers.',
                'source_url' => 'https://sonar.software/pricing',
                'source_label' => 'Sonar pricing page',
                'cards' => [
                    ['label' => 'SkyBase entry', 'value' => '$0/month', 'note' => 'Free up to 40 subscribers'],
                    ['label' => 'Paid SkyBase', 'value' => '$69/month', 'note' => 'Starter plan up to 150 subscribers'],
                    ['label' => 'Sonar public floor', 'value' => '$500/month', 'note' => 'With contract, as publicly listed'],
                ],
                'rows' => [
                    ['feature' => 'Primary fit', 'skybase' => 'MikroTik-first cloud ISP operations', 'competitor' => 'Broad broadband operations platform'],
                    ['feature' => 'Starting point', 'skybase' => 'Free plan up to 40 subscribers', 'competitor' => 'Public pricing starts at $500/month with contract'],
                    ['feature' => 'Pricing shape', 'skybase' => 'Simple published tiers by subscriber count', 'competitor' => 'Active-subscriber tiers by scale'],
                    ['feature' => 'Operations scope', 'skybase' => 'Customers, Radius, routers, billing follow-up, and support', 'competitor' => 'Billing, field service, inventory, reporting, ticketing, and network workflows'],
                    ['feature' => 'Support path', 'skybase' => '24/7 WhatsApp support for setup and urgent questions', 'competitor' => 'Published support team and learning resources'],
                    ['feature' => 'Best decision', 'skybase' => 'Choose SkyBase when a smaller MikroTik ISP needs fast cloud adoption', 'competitor' => 'Choose Sonar when a larger team needs a broader operations stack'],
                ],
                'faqs' => [
                    ['question' => 'Is SkyBase a Sonar alternative?', 'answer' => 'Yes, for MikroTik ISPs that want cloud Radius, customer workflows, router visibility, billing follow-up, and support in a simpler package. Sonar may be a better fit for larger providers that need a wider operations suite.'],
                    ['question' => 'Why would an ISP choose SkyBase instead of Sonar?', 'answer' => 'SkyBase gives small and growing MikroTik operators a lower-friction path: free entry, clear monthly tiers, cloud hosting, and direct setup help without starting from a larger platform commitment.'],
                    ['question' => 'Can I migrate from Sonar to SkyBase?', 'answer' => 'Yes. We can review your current customers, plans, billing workflow, routers, and Radius setup during a demo, then plan the cleanest migration path.'],
                ],
            ],
        ];

        return view('alternatives.show', [
            'page' => $pages[$competitor],
        ]);
    }
}
