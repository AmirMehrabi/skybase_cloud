@extends('layouts.layout')

@section('title', 'Your first PPPoE or Hotspot subscription | SkyBase Guide')
@section('meta_description', 'A step-by-step guide to your first SkyBase subscriber: connect a MikroTik router, add a customer and plan, set up IPAM and RouterOS, and test the connection.')
@section('body_class', 'bg-[#fffdf8] text-[#17211f]')

@php
    $isHotspot = $serviceType === 'hotspot';
    $serviceName = $isHotspot ? 'Hotspot' : 'PPPoE';
    $chapters = [
        'before-you-start' => 'Before you start',
        'router' => '1. Connect your router',
        'customer' => '2. Add a customer',
        'plan' => '3. Create a plan',
        'ipam' => '4. Choose IP addressing',
        'routeros' => '5. Set up '.$serviceName,
        'subscription' => '6. Create the subscription',
        'test' => '7. Connect and check',
        'troubleshooting' => 'Need a hand?',
    ];
    $setupSteps = [
        [
            'id' => 'customer', 'number' => '02', 'title' => 'Add the person using the service',
            'intro' => 'The customer record holds their contact and billing details. Their subscription will hold the internet service and login.',
            'route' => 'customers.create', 'action' => 'Open New Customer', 'image' => 'customer',
            'caption' => 'Customer form: start with the required contact and address fields.',
            'instructions' => [
                'Open Customers → New Customer. Choose Individual or Business and fill in the fields marked with an asterisk (*).',
                'Enter their email, mobile number, and service address. Set the correct country and choose Prepaid or Postpaid to match your billing arrangement.',
                'Portal Password is optional. It is for the customer portal and is separate from the internet login you will create later.',
                'Organizations are optional for a simple first setup. If you use organization billing, the primary organization can supply the plan and billing defaults.',
                'Choose Create Customer & Add Subscription. If the subscription form opens, come back here to prepare the plan and addresses before completing it.',
            ],
            'success' => 'The customer is saved and available in the subscription’s Customer list. Their status must be Active for RADIUS authentication.',
        ],
        [
            'id' => 'plan', 'number' => '03', 'title' => 'Decide what internet service you sell',
            'intro' => 'A plan defines the speed, data allowance, and price. You can reuse the same plan for more customers.',
            'route' => 'plans.create', 'action' => 'Open Create Plan', 'image' => 'plan',
            'caption' => 'Plan form: name and service type first, then speed and billing.',
            'instructions' => [
                'Open Plans → Create Plan. Give it a clear Plan Name, such as Home 20 Mbps, and a unique Internal Name, such as home_20.',
                'Set Service Type to '.$serviceName.' and keep Status set to Active.',
                'For a simple setup, use Basic rate limit. Enter Download Speed and Upload Speed, checking the selected speed unit.',
                'Choose Unlimited Data or enter a Data Limit. Review Data Cap Action so you know what happens when the allowance runs out.',
                'Set the price, currency, Billing Cycle, and Grace Period. Review any Setup Fee, then choose Save Plan.',
            ],
            'success' => 'Your active plan appears in the Service Plan list. The subscription inherits its billing cycle.',
        ],
    ];
@endphp

@section('content')
<main id="guide-top" x-data="firstSubscriptionGuide">
    <section class="border-b border-[#17211f]/10 bg-[#f7f3ea]">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#145a5a]">The getting started guide</p>
            <h1 class="mt-4 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">Your first subscriber.<br>One clear step at a time.</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-[#52605d]">Connect your MikroTik, prepare the service, and get one customer online. Follow along in SkyBase and Winbox, then check the connection together.</p>
            <div class="mt-8 grid max-w-2xl gap-3 sm:grid-cols-2" aria-label="Choose your service">
                @foreach(['pppoe' => ['PPPoE', 'For a customer router that dials in with a username and password.'], 'hotspot' => ['Hotspot', 'For Wi-Fi users who sign in through a browser login page.']] as $type => $option)
                    <a href="{{ route('guide', ['service' => $type]) }}" @if($serviceType === $type) aria-current="page" @endif @class(['rounded-xl border-2 p-5 transition focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none', 'border-[#145a5a] bg-white' => $serviceType === $type, 'border-[#17211f]/10 hover:border-[#145a5a]/50' => $serviceType !== $type])>
                        <span class="flex items-center justify-between font-bold">{{ $option[0] }} <span aria-hidden="true">{{ $serviceType === $type ? '✓' : '→' }}</span></span>
                        <span class="mt-2 block text-sm leading-6 text-[#52605d]">{{ $option[1] }}</span>
                    </a>
                @endforeach
            </div>
            <p class="mt-4 text-sm text-[#52605d]">You’re following the <strong>{{ $serviceName }}</strong> path. Switch at any time. Already finished a step? Jump ahead below.</p>
            <a href="#before-you-start" class="mt-6 inline-flex rounded-xl bg-[#f5c542] px-6 py-3 font-semibold text-[#17211f] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2">Let’s get started ↓</a>
        </div>
    </section>

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-14 lg:px-8 lg:py-14">
        <aside>
            <details class="rounded-xl border border-[#17211f]/15 bg-[#f7f3ea] lg:hidden">
                <summary class="cursor-pointer px-5 py-4 font-semibold text-[#145a5a] focus:outline-none focus:ring-2 focus:ring-[#145a5a]">Jump to a step · {{ $serviceName }}</summary>
                <x-guide-navigation :chapters="$chapters" :service-name="$serviceName" />
            </details>
            <div class="hidden lg:sticky lg:top-28 lg:block">
                <x-guide-navigation :chapters="$chapters" :service-name="$serviceName" />
            </div>
        </aside>

        <div class="min-w-0 space-y-14 leading-7">
            <section id="before-you-start" class="scroll-mt-28">
                <p class="text-sm font-bold text-[#145a5a]">A little preparation</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight">Before you start</h2>
                <p class="mt-4 text-[#52605d]">Have your SkyBase account open and a MikroTik router you can manage in Winbox. The router needs a working internet uplink and a separate customer-facing interface or bridge.</p>
                <ul class="mt-4 list-disc space-y-2 pl-5 text-[#52605d]">
                    <li><strong class="text-[#17211f]">Your connection details:</strong> the router address SkyBase can reach, its API account, and SSH access if your setup uses it.</li>
                    <li><strong class="text-[#17211f]">Your RADIUS details:</strong> ask your SkyBase administrator for the server address, shared secret, and the source/NAS address registered for your router. A website address is not necessarily your RADIUS server.</li>
                    <li><strong class="text-[#17211f]">Your customer network:</strong> an unused subnet, a gateway, and working DNS. We use 10.20.0.0/24 and gateway 10.20.0.1 as examples. Replace them if they overlap your network.</li>
                    <li><strong class="text-[#17211f]">A test device:</strong> {{ $isHotspot ? 'a phone or laptop that can join the customer Wi-Fi.' : 'a customer router or computer with a PPPoE client.' }}</li>
                </ul>
                <div class="mt-5 rounded-xl border border-[#145a5a]/20 bg-[#145a5a]/5 p-5 text-sm">You only need to set up the router, plan, and address pool once. For your next subscriber, you can reuse them and start with the customer.</div>
                <x-guide-next anchor="router" label="Connect your router" />
            </section>

            <section id="router" class="scroll-mt-28 border-t border-[#17211f]/10 pt-10">
                <p class="text-sm font-bold text-[#145a5a]">Step 01 · SkyBase + Winbox</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight">Connect your router</h2>
                <p class="mt-4 text-[#52605d]">SkyBase needs a way to talk to your router. Subscriber login uses RADIUS; router monitoring uses the RouterOS API. Both connections need to work.</p>
                <ol class="mt-5 list-decimal space-y-3 pl-5 text-[#52605d]">
                    <li>In Winbox, open <strong>IP → Services</strong>. Check the API service and port agreed for your installation. Use API-SSL when your SkyBase installation requires TLS. Allow access from the SkyBase server or management VPN.</li>
                    <li>Open <strong>System → Users</strong> and prepare a dedicated management account with the permissions your installation needs. Keep its username and password ready.</li>
                    <li>In SkyBase, open <strong>Routers → Add New Router</strong>. Enter Router Name, select Mikrotik, and enter the reachable IP Address, API Port, API Username, and API Password. Match the SSH Port if SSH is used.</li>
                    <li>Leave Enable Monitoring and Enable Provisioning enabled for this setup, then choose <strong>Save Router</strong>. NetFlow can wait until after the first subscriber works.</li>
                </ol>
                <a href="{{ route('routers.create') }}" target="_blank" rel="noopener" class="mt-5 inline-flex font-semibold text-[#145a5a] underline underline-offset-4">Open Add New Router ↗ <span class="sr-only">(new tab)</span></a>
                <x-guide-screenshot image="router" alt="SkyBase Add New Router form showing connection settings" caption="Match the connection settings to your router. No real credentials are shown." />
                <p class="mt-5 rounded-xl bg-[#f7f3ea] p-5 text-sm"><strong>Ready to move on:</strong> the router is saved and SkyBase can reach it. If it stays Offline, check the management address, service port, account permissions, and firewall before continuing.</p>
                <x-guide-next anchor="customer" label="Add a customer" />
            </section>

            @foreach($setupSteps as $step)
                <section id="{{ $step['id'] }}" class="scroll-mt-28 border-t border-[#17211f]/10 pt-10">
                    <p class="text-sm font-bold text-[#145a5a]">Step {{ $step['number'] }} · SkyBase</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight">{{ $step['title'] }}</h2>
                    <p class="mt-4 text-[#52605d]">{{ $step['intro'] }}</p>
                    <ol class="mt-5 list-decimal space-y-3 pl-5 text-[#52605d]">
                        @foreach($step['instructions'] as $instruction)
                            <li>{{ $instruction }}</li>
                        @endforeach
                    </ol>
                    <a href="{{ route($step['route']) }}" target="_blank" rel="noopener" class="mt-5 inline-flex font-semibold text-[#145a5a] underline underline-offset-4">{{ $step['action'] }} ↗ <span class="sr-only">(new tab)</span></a>
                    <x-guide-screenshot :image="$step['image']" :alt="$step['title'].' — SkyBase form'" :caption="$step['caption']" />
                    <p class="mt-5 rounded-xl bg-[#f7f3ea] p-5 text-sm"><strong>Ready to move on:</strong> {{ $step['success'] }}</p>
                    <x-guide-next :anchor="$step['id'] === 'customer' ? 'plan' : 'ipam'" :label="$step['id'] === 'customer' ? 'Create a plan' : 'Choose IP addressing'" />
                </section>
            @endforeach

            <section id="ipam" class="scroll-mt-28 border-t border-[#17211f]/10 pt-10">
                <p class="text-sm font-bold text-[#145a5a]">Step 04 · SkyBase</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight">Choose who assigns the IP address</h2>
                <p class="mt-4 text-[#52605d]">Every connected device needs an IP address. Pick one way to manage it and carry that choice into the subscription.</p>
                @if($isHotspot)
                    <div class="mt-5 rounded-xl border border-[#145a5a]/20 bg-[#145a5a]/5 p-5"><strong>For your first Hotspot user, use Router Managed.</strong> The Hotspot’s DHCP pool gives the device an address before the user logs in. You can review IPAM here, but you do not need a SkyBase pool for this path.</div>
                @else
                    <div class="mt-5 rounded-xl border border-[#145a5a]/20 bg-[#145a5a]/5 p-5"><strong>For this PPPoE walkthrough, use System Managed.</strong> SkyBase tracks the subscriber’s address in IPAM and supplies it through RADIUS. Router Managed is also available if you already manage your PPP address pool on the MikroTik.</div>
                @endif
                <h3 class="mt-6 text-xl font-bold">To create a SkyBase IPAM pool</h3>
                <ol class="mt-4 list-decimal space-y-3 pl-5 text-[#52605d]">
                    <li>Open <strong>IP Address Management → Manage Pools → Create Pool</strong>. Enter a Pool Name and choose Dynamic (DHCP/PPPoE) or Mixed (Both).</li>
                    <li>Under Device Assignment, select your router. Use All current and future devices only if the same pool should be available to all your routers.</li>
                    <li>Enter the Network Address, CIDR Prefix, Gateway IP, and DNS. Example: 10.20.0.0, /24, and 10.20.0.1.</li>
                    <li>Reserve any addresses already used by infrastructure. Review the pool preview and assignment settings, then choose <strong>Create Pool</strong>.</li>
                </ol>
                <a href="{{ route('ipam.pools.create') }}" target="_blank" rel="noopener" class="mt-5 inline-flex font-semibold text-[#145a5a] underline underline-offset-4">Open Create Pool ↗ <span class="sr-only">(new tab)</span></a>
                <x-guide-screenshot image="ipam" alt="SkyBase IPAM network configuration showing subnet, gateway, DNS, and reserved addresses" caption="Use a subnet that belongs to your customer network. The addresses shown are examples." />
                <p class="mt-5 rounded-xl bg-[#f7f3ea] p-5 text-sm"><strong>Ready to move on:</strong> for System Managed, the active pool has available addresses and is assigned to your router. Creating an IPAM pool does not configure interfaces, routing, or DHCP on the MikroTik.</p>
                <x-guide-next anchor="routeros" :label="'Set up '.$serviceName" />
            </section>

            <section id="routeros" class="scroll-mt-28 border-t border-[#17211f]/10 pt-10">
                <p class="text-sm font-bold text-[#145a5a]">Step 05 · Winbox / RouterOS</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight">Prepare {{ $serviceName }} on the MikroTik</h2>
                <p class="mt-4 text-[#52605d]">Use the Winbox steps below. The terminal examples are an alternative for people comfortable with RouterOS. Replace every value in CAPITALS with the details for your installation before running a command.</p>
                <h3 class="mt-6 text-xl font-bold">First, point the router to RADIUS</h3>
                <p class="mt-3 text-[#52605d]">In Winbox, open <strong>RADIUS → +</strong>. Select <strong>{{ $isHotspot ? 'hotspot' : 'ppp' }}</strong>, enter your RADIUS server Address and Secret, and use the registered Src. Address if your installation requires it. Standard UDP ports are 1812 for authentication and 1813 for accounting; use your administrator’s values if different.</p>
                <pre class="mt-4 overflow-x-auto rounded-xl bg-[#0d2f35] p-5 text-sm leading-7 text-white" tabindex="0" aria-label="RADIUS configuration example"><code>/radius add service={{ $isHotspot ? 'hotspot' : 'ppp' }} address=RADIUS_SERVER_IP \
    secret="YOUR_RADIUS_SECRET" src-address=REGISTERED_ROUTER_IP</code></pre>
                <p class="mt-3 text-sm text-[#52605d]">If a matching RADIUS entry already exists, edit it instead of adding another one. The server must recognize this router as a client of your SkyBase workspace. Saving the router in SkyBase alone does not confirm that registration.</p>
                @if($isHotspot)
                    <h3 class="mt-7 text-xl font-bold">Create the Wi-Fi login service</h3>
                    <ol class="mt-4 list-decimal space-y-3 pl-5 text-[#52605d]">
                        <li>In <strong>IP → Hotspot → Hotspot Setup</strong>, choose the customer-facing interface or bridge. Keep your internet uplink outside that customer bridge.</li>
                        <li>Follow the wizard with your customer subnet, gateway, DHCP address range, DNS, and login hostname. Use an appropriate certificate for HTTPS login. Match these settings to your network.</li>
                        <li>Open <strong>IP → Hotspot → Server Profiles</strong>, edit the profile used by your Hotspot, then open its <strong>RADIUS</strong> tab. Enable Use RADIUS, accounting, and interim updates for usage reporting.</li>
                        <li>For a first login test, use a new username that does not already exist under <strong>IP → Hotspot → Users</strong>. A local user can take precedence over RADIUS.</li>
                    </ol>
                    <details class="mt-5 rounded-xl border border-[#17211f]/15 p-5">
                        <summary class="cursor-pointer font-semibold text-[#145a5a]">RouterOS terminal alternative</summary>
                        <p class="mt-3 text-sm text-[#52605d]">Run the setup wizard only if the service is not configured yet. Replace YOUR_HOTSPOT_PROFILE with the profile created or selected in the wizard.</p>
                        <pre class="mt-4 overflow-x-auto rounded-lg bg-[#0d2f35] p-4 text-sm text-white" tabindex="0"><code>/ip hotspot setup
/ip hotspot profile set [find name="YOUR_HOTSPOT_PROFILE"] \
    use-radius=yes radius-accounting=yes radius-interim-update=5m</code></pre>
                    </details>
                    <p class="mt-5 rounded-xl bg-[#f7f3ea] p-5 text-sm"><strong>Ready to move on:</strong> a phone joining the customer network receives an address and can reach the Hotspot login page. The SkyBase internet login is created in the next step.</p>
                @else
                    <h3 class="mt-7 text-xl font-bold">Create the PPPoE service</h3>
                    <ol class="mt-4 list-decimal space-y-3 pl-5 text-[#52605d]">
                        <li>Open <strong>PPP → Profiles → +</strong>. Name the profile, set Local Address to the customer gateway (10.20.0.1 in our example), and set working DNS servers.</li>
                        <li>With System Managed addressing, RADIUS supplies the subscriber address. If you chose Router Managed, first create a pool under <strong>IP → Pool</strong> and select it as the profile’s Remote Address.</li>
                        <li>Open <strong>PPP → PPPoE Servers → +</strong>. Choose the customer-facing interface or bridge, give the service a name, select your Default Profile, and enable it.</li>
                        <li>Open <strong>PPP → Secrets → PPP Authentication & Accounting (AAA)</strong>. Enable Use RADIUS and Accounting, with Interim Update set to 5 minutes.</li>
                    </ol>
                    <details class="mt-5 rounded-xl border border-[#17211f]/15 p-5">
                        <summary class="cursor-pointer font-semibold text-[#145a5a]">RouterOS terminal alternative</summary>
                        <p class="mt-3 text-sm text-[#52605d]">This example uses System Managed addresses. Replace the interface and DNS values. Reuse your existing profile/server if one is already configured.</p>
                        <pre class="mt-4 overflow-x-auto rounded-lg bg-[#0d2f35] p-4 text-sm text-white" tabindex="0"><code>/ppp profile add name=skybase-ppp local-address=10.20.0.1 \
    dns-server=YOUR_DNS_IP
/interface pppoe-server server add service-name=skybase \
    interface=CUSTOMER_INTERFACE default-profile=skybase-ppp disabled=no
/ppp aaa set use-radius=yes accounting=yes interim-update=5m</code></pre>
                    </details>
                    <p class="mt-5 rounded-xl bg-[#f7f3ea] p-5 text-sm"><strong>Ready to move on:</strong> the PPPoE server is enabled on the customer interface and its AAA settings use RADIUS. Avoid an existing local PPP Secret with your test username.</p>
                @endif
                <details class="mt-5 rounded-xl border border-[#17211f]/15 p-5">
                    <summary class="cursor-pointer font-semibold text-[#145a5a]">Allow SkyBase to disconnect a session</summary>
                    <p class="mt-3 text-sm text-[#52605d]">For remote session termination, open Winbox <strong>RADIUS → Incoming</strong> and enable Accept on the agreed port (the default is 1700). Match that port and the RADIUS shared secret in SkyBase’s CoA Port and CoA Secret fields. Permit incoming disconnect traffic only from your RADIUS server. API Password, subscriber password, and RADIUS shared secret serve different purposes.</p>
                </details>
                <p class="mt-5 text-sm text-[#52605d]">Keep your working internet route, DNS, and required NAT/firewall rules in place. This guide configures subscriber access; it does not replace your router’s uplink setup.</p>
                <p class="mt-4 text-sm text-[#52605d]">RouterOS reference: <a href="https://manual.mikrotik.com/docs/authentication-authorization-accounting/radius/" target="_blank" rel="noopener" class="text-[#145a5a] underline">RADIUS</a>, <a href="https://manual.mikrotik.com/docs/authentication-authorization-accounting/ppp-aaa/" target="_blank" rel="noopener" class="text-[#145a5a] underline">PPP AAA</a>, <a href="https://manual.mikrotik.com/docs/virtual-private-networks/pppoe/" target="_blank" rel="noopener" class="text-[#145a5a] underline">PPPoE</a>, and <a href="https://manual.mikrotik.com/docs/authentication-authorization-accounting/hotspot-captive-portal/" target="_blank" rel="noopener" class="text-[#145a5a] underline">Hotspot</a>. Menu wording can vary with your Winbox version.</p>
                <x-guide-next anchor="subscription" label="Create the subscription" />
            </section>

            <section id="subscription" class="scroll-mt-28 border-t border-[#17211f]/10 pt-10">
                <p class="text-sm font-bold text-[#145a5a]">Step 06 · SkyBase</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight">Bring it together in a subscription</h2>
                <p class="mt-4 text-[#52605d]">You now have a customer, a plan, and a router. A subscription joins them and creates the customer’s internet login.</p>
                <ol class="mt-5 list-decimal space-y-3 pl-5 text-[#52605d]">
                    <li>Open <strong>Subscriptions → Create Subscription</strong>. Select your Customer and enter a Name you will recognize.</li>
                    <li>Set <strong>Subscription Type to {{ $serviceName }}</strong>, select your Service Plan, and choose the Router / NAS you just prepared.</li>
                    <li>Under <strong>Connection Type, choose PPP</strong> for this username/password RADIUS walkthrough. Enter a unique PPP Username and a strong PPP Password.</li>
                    <li>Choose <strong>{{ $isHotspot ? 'Router Managed' : 'System Managed' }}</strong> under IP Management. {{ $isHotspot ? 'Your Hotspot DHCP pool assigns the device address.' : 'Select your IP Pool and a free Primary IP Address, or use the automatic assignment option. If you chose Router Managed earlier, choose that here instead.' }}</li>
                    <li>Review Line Items and the total. The Billing Cycle comes from the plan. Check Billing Enabled and Auto Suspension Enabled so they match the service you intend to provide.</li>
                    <li>Keep Initial Status set to <strong>Active</strong> if you want to connect now. Check the dates and choose <strong>Create Subscription</strong>. Pending Activation is for services you will activate later.</li>
                </ol>
                @if($isHotspot)
                    <div class="mt-5 rounded-xl border border-[#145a5a]/20 bg-[#145a5a]/5 p-5 text-sm"><strong>Why does a Hotspot user have “PPP” fields?</strong> In the current SkyBase form, this connection option stores the username and password used by RADIUS. Keep Subscription Type as Hotspot. The customer will use these credentials on the Wi-Fi login page; they do not need to dial a PPPoE connection.</div>
                @endif
                <a href="{{ route('subscriptions.create') }}" target="_blank" rel="noopener" class="mt-5 inline-flex font-semibold text-[#145a5a] underline underline-offset-4">Open Create Subscription ↗ <span class="sr-only">(new tab)</span></a>
                <x-guide-screenshot :image="'subscription-'.$serviceType" :alt="'Create Subscription form with '.$serviceName.' selected and PPP credentials'" caption="Select the service type, then add credentials, addressing, and billing. Expand to see the whole form." />
                <p class="mt-5 rounded-xl bg-[#f7f3ea] p-5 text-sm"><strong>Ready to move on:</strong> the saved subscription shows the correct customer, plan, router, and Active status. Provisioning may finish in the background; Active alone does not prove the customer is online.</p>
                <x-guide-next anchor="test" label="Connect and check" />
            </section>

            <section id="test" class="scroll-mt-28 border-t border-[#17211f]/10 pt-10">
                <p class="text-sm font-bold text-[#145a5a]">Step 07 · One real connection</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight">Connect and check it works</h2>
                <ol class="mt-5 list-decimal space-y-3 pl-5 text-[#52605d]">
                    @if($isHotspot)
                        <li>Join the customer Wi-Fi from a test phone or laptop. Open the captive portal and enter the subscription’s PPP Username and PPP Password.</li>
                        <li>In Winbox, open <strong>IP → Hotspot → Active</strong>. Your username should appear with a client address.</li>
                    @else
                        <li>On the customer’s router, configure its WAN as PPPoE and enter the subscription’s PPP Username and PPP Password. Connect it to the customer network served by your MikroTik.</li>
                        <li>In Winbox, open <strong>PPP → Active Connections</strong>. Your username should appear with the expected remote address.</li>
                    @endif
                    <li>Open a website from the test device and run a speed test. Compare the result with the plan, allowing for Wi-Fi quality and available uplink capacity.</li>
                    <li>Return to the subscription in SkyBase. Check its connection status and session/authentication information. With accounting and interim updates enabled, usage should start appearing after traffic and an update interval.</li>
                </ol>
                <div class="mt-6 rounded-xl bg-[#0d2f35] p-6 text-white"><h3 class="text-xl font-bold">Your first subscriber is ready.</h3><p class="mt-2 text-white/80">The login works, the customer can reach the internet, and you can see the session. For the next customer, reuse your router and plan, then create their own subscription and credentials.</p></div>
            </section>

            <section id="troubleshooting" class="scroll-mt-28 border-t border-[#17211f]/10 pt-10">
                <h2 class="text-3xl font-bold tracking-tight">Stuck? Start with what you see.</h2>
                @foreach([
                    'The router stays Offline' => 'Check the IP SkyBase can reach, management VPN or route, API service and port, TLS requirements, firewall, and management account permissions. Router API connectivity and subscriber RADIUS connectivity are separate checks.',
                    'The login is rejected' => 'Check the username/password, Active customer and plan, and Active subscription. Confirm the router is registered with your RADIUS administrator. In this walkthrough, Connection Type must be PPP, even for Hotspot. Check for a local user with the same name that could override RADIUS.',
                    'RADIUS requests time out or show bad replies' => 'In Winbox, open the RADIUS entry and its status counters. Timeouts suggest reachability, firewall, or server/client registration issues; bad replies can indicate a shared-secret mismatch. Check the configured source address with your administrator.',
                    'My IP pool is missing or has no free addresses' => 'Check that the pool is Active, assigned to the selected router, and has available addresses. Reserved or already assigned addresses cannot be selected. For the Hotspot Router Managed path, use the MikroTik DHCP pool instead.',
                    'The customer logs in but has no internet' => 'Check the customer gateway, DNS, uplink route, and NAT/firewall rules. A successful login confirms authentication; the router still needs to route the customer traffic.',
                    'The customer is online but SkyBase shows no usage' => 'Check RADIUS accounting, the accounting port, and interim updates on the correct PPP or Hotspot profile. Generate traffic and allow an update interval. If activation is still processing, ask your administrator to check background jobs.',
                ] as $question => $answer)
                    <details class="mt-3 rounded-xl border border-[#17211f]/15 px-5 py-4">
                        <summary class="cursor-pointer py-1 font-semibold focus:outline-none focus:ring-2 focus:ring-[#145a5a]">{{ $question }}</summary>
                        <p class="mt-3 text-[#52605d]">{{ $answer }}</p>
                    </details>
                @endforeach
                <p class="mt-6 text-[#52605d]">Still need help? <a href="{{ route('contact.show') }}" class="font-semibold text-[#145a5a] underline underline-offset-4">Contact the SkyBase team</a> with the step you reached and the error message.</p>
                <a href="#guide-top" class="mt-6 inline-flex font-semibold text-[#145a5a] underline underline-offset-4">Back to the start ↑</a>
            </section>
        </div>
    </div>
</main>
@endsection
