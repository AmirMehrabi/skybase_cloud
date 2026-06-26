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
