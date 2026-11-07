<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(13,148,136,0.14),_transparent_35%),linear-gradient(180deg,_#f8fafc_0%,_#ecfeff_100%)] text-slate-800">
    <div class="lg:flex">
        <aside class="lg:sticky lg:top-0 lg:h-screen lg:w-80 shrink-0 border-b border-white/60 bg-slate-950 px-6 py-8 text-slate-100 lg:border-b-0 lg:border-r">
            <div class="mb-8">
                <p class="text-sm uppercase tracking-[0.35em] text-teal-300">Avinox CRM</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight">API Docs</h1>
                <p class="mt-3 text-sm leading-6 text-slate-300">Authentication, customer lookup, subscription lookup, services listing, and lead API reference for <span class="font-semibold text-white">https://crm.avinox.ir</span>.</p>
            </div>

            <nav class="space-y-2 text-sm">
                <a href="#overview" class="block rounded-xl border border-white/10 px-4 py-3 hover:border-teal-400 hover:bg-white/5">Overview</a>
                <a href="#authentication" class="block rounded-xl border border-white/10 px-4 py-3 hover:border-teal-400 hover:bg-white/5">Authentication</a>
                <a href="#customers" class="block rounded-xl border border-white/10 px-4 py-3 hover:border-teal-400 hover:bg-white/5">Customer APIs</a>
                <a href="#subscriptions" class="block rounded-xl border border-white/10 px-4 py-3 hover:border-teal-400 hover:bg-white/5">Subscription APIs</a>
                <a href="#services" class="block rounded-xl border border-white/10 px-4 py-3 hover:border-teal-400 hover:bg-white/5">Service APIs</a>
                <a href="#leads" class="block rounded-xl border border-white/10 px-4 py-3 hover:border-teal-400 hover:bg-white/5">Lead APIs</a>
                <a href="#errors" class="block rounded-xl border border-white/10 px-4 py-3 hover:border-teal-400 hover:bg-white/5">Responses & Errors</a>
            </nav>
        </aside>

        <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-10">
            <section id="overview" class="rounded-3xl border border-white/70 bg-white/80 p-8 shadow-soft backdrop-blur">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-brand">REST API Reference</p>
                        <h2 class="mt-3 text-4xl font-black tracking-tight text-ink">Fast lookup endpoints for customers, subscriptions, services, and leads</h2>
                        <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600">These APIs are served from the CRM application. Authenticated v1 endpoints use API key authentication through the <code class="rounded bg-slate-100 px-2 py-1 text-sm">X-API-Key</code> header or a <code class="rounded bg-slate-100 px-2 py-1 text-sm">Bearer</code> token in the <code class="rounded bg-slate-100 px-2 py-1 text-sm">Authorization</code> header.</p>
                    </div>
                    <div class="rounded-2xl border border-teal-200 bg-mist p-5 text-sm text-slate-700">
                        <p class="font-semibold text-brand">Base URL</p>
                        <code class="mt-2 block text-base font-bold text-slate-900">https://crm.avinox.ir/api</code>
                    </div>
                </div>
            </section>

            <section id="authentication" class="mt-8 rounded-3xl border border-white/70 bg-white/90 p-8 shadow-soft">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-brand/10 p-3 text-brand">01</div>
                    <h2 class="text-2xl font-extrabold text-ink">Authentication</h2>
                </div>
                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 p-6">
                        <h3 class="text-lg font-bold text-slate-900">How it works</h3>
                        <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                            <li>Authenticated <code class="rounded bg-slate-100 px-1.5 py-0.5">/api/v1/*</code> endpoints use the custom <code class="rounded bg-slate-100 px-1.5 py-0.5">auth.api</code> middleware.</li>
                            <li>Send your API key in <code class="rounded bg-slate-100 px-1.5 py-0.5">X-API-Key</code> or as <code class="rounded bg-slate-100 px-1.5 py-0.5">Authorization: Bearer YOUR_KEY</code>.</li>
                            <li>Invalid, missing, or expired keys return <code class="rounded bg-slate-100 px-1.5 py-0.5">401 Unauthorized</code>.</li>
                            <li>Authenticated routes are rate-limited with the <code class="rounded bg-slate-100 px-1.5 py-0.5">leads-api</code> limiter.</li>
                        </ul>
                    </div>
                    <div class="rounded-2xl bg-slate-950 p-6 text-slate-100">
<pre class="overflow-x-auto text-sm leading-7"><code>GET /api/v1/customers/by-phone?phone=09123456789
Host: crm.avinox.ir
X-API-Key: YOUR_API_KEY
Accept: application/json

# or
Authorization: Bearer YOUR_API_KEY</code></pre>
                    </div>
                </div>
            </section>

            <section id="customers" class="mt-8 rounded-3xl border border-white/70 bg-white/90 p-8 shadow-soft">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-brand/10 p-3 text-brand">02</div>
                    <h2 class="text-2xl font-extrabold text-ink">Customer APIs</h2>
                </div>

                <div class="mt-6 space-y-6">
                    <article class="rounded-2xl border border-slate-200 p-6">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">POST</span>
                            <code class="text-sm font-semibold text-slate-900">/api/v1/customers</code>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-slate-600">Creates a new customer using the existing authenticated customer creation API.</p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 p-6">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.25em] text-sky-700">GET</span>
                            <code class="text-sm font-semibold text-slate-900">/api/v1/customers/by-phone</code>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-slate-600">Finds a customer by exact <code class="rounded bg-slate-100 px-1.5 py-0.5">phone</code> value and returns the customer with all subscriptions, plus service details for each subscription.</p>
                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Query Parameters</p>
                                <ul class="mt-3 text-sm leading-7 text-slate-700">
                                    <li><code>phone</code> - required, exact customer phone number</li>
                                </ul>
                            </div>
                            <div class="rounded-2xl bg-slate-950 p-4 text-slate-100">
<pre class="overflow-x-auto text-sm leading-7"><code>curl --request GET \
  --url 'https://crm.avinox.ir/api/v1/customers/by-phone?phone=09123456789' \
  --header 'X-API-Key: YOUR_API_KEY' \
  --header 'Accept: application/json'</code></pre>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section id="subscriptions" class="mt-8 rounded-3xl border border-white/70 bg-white/90 p-8 shadow-soft">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-brand/10 p-3 text-brand">03</div>
                    <h2 class="text-2xl font-extrabold text-ink">Subscription APIs</h2>
                </div>

                <article class="mt-6 rounded-2xl border border-slate-200 p-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.25em] text-sky-700">GET</span>
                        <code class="text-sm font-semibold text-slate-900">/api/v1/subscriptions/by-pppoe</code>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-600">Finds a single subscription directly by <code class="rounded bg-slate-100 px-1.5 py-0.5">pppoe_username</code> and returns the subscription, service, and customer snapshot.</p>
                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Query Parameters</p>
                            <ul class="mt-3 text-sm leading-7 text-slate-700">
                                <li><code>pppoe_username</code> - required, exact PPPoE username</li>
                            </ul>
                        </div>
                        <div class="rounded-2xl bg-slate-950 p-4 text-slate-100">
<pre class="overflow-x-auto text-sm leading-7"><code>curl --request GET \
  --url 'https://crm.avinox.ir/api/v1/subscriptions/by-pppoe?pppoe_username=john.doe' \
  --header 'Authorization: Bearer YOUR_API_KEY' \
  --header 'Accept: application/json'</code></pre>
                        </div>
                    </div>
                </article>
            </section>

            <section id="services" class="mt-8 rounded-3xl border border-white/70 bg-white/90 p-8 shadow-soft">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-brand/10 p-3 text-brand">04</div>
                    <h2 class="text-2xl font-extrabold text-ink">Service APIs</h2>
                </div>

                <article class="mt-6 rounded-2xl border border-slate-200 p-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.25em] text-sky-700">GET</span>
                        <code class="text-sm font-semibold text-slate-900">/api/v1/services</code>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-600">Returns all services with their related packages and key commercial and technical fields such as price, duration, rates, package type, technology, and status.</p>
                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Authentication</p>
                            <ul class="mt-3 text-sm leading-7 text-slate-700">
                                <li>Requires <code>X-API-Key</code> or <code>Authorization: Bearer YOUR_API_KEY</code></li>
                                <li>No query parameters are required</li>
                            </ul>
                        </div>
                        <div class="rounded-2xl bg-slate-950 p-4 text-slate-100">
<pre class="overflow-x-auto text-sm leading-7"><code>curl --request GET \
  --url 'https://crm.avinox.ir/api/v1/services' \
  --header 'X-API-Key: YOUR_API_KEY' \
  --header 'Accept: application/json'</code></pre>
                        </div>
                    </div>
                    <div class="mt-4 rounded-2xl bg-slate-950 p-4 text-slate-100">
<pre class="overflow-x-auto text-sm leading-7"><code>{
  "success": true,
  "message": "Services retrieved successfully",
  "data": {
    "services": [
      {
        "id": 7,
        "name": "100Mbps Fiber",
        "price": 850000,
        "duration_value": 1,
        "duration_unit": "month",
        "package_type": "Shared Package",
        "tech_code": "PPP",
        "download_rate": 100,
        "upload_rate": 50,
        "status": "active",
        "packages": [
          {
            "id": 4,
            "name": "Starter",
            "price": 850000
          }
        ]
      }
    ]
  }
}</code></pre>
                    </div>
                </article>
            </section>

            <section id="leads" class="mt-8 rounded-3xl border border-white/70 bg-white/90 p-8 shadow-soft">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-brand/10 p-3 text-brand">05</div>
                    <h2 class="text-2xl font-extrabold text-ink">Lead APIs</h2>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">GET</p><code class="mt-2 block text-sm font-semibold">/api/v1/leads</code><p class="mt-3 text-sm text-slate-600">List leads.</p></div>
                    <div class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">POST</p><code class="mt-2 block text-sm font-semibold">/api/v1/leads</code><p class="mt-3 text-sm text-slate-600">Create a lead.</p></div>
                    <div class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">GET</p><code class="mt-2 block text-sm font-semibold">/api/v1/leads/{lead}</code><p class="mt-3 text-sm text-slate-600">Show a single lead.</p></div>
                    <div class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">PUT</p><code class="mt-2 block text-sm font-semibold">/api/v1/leads/{lead}</code><p class="mt-3 text-sm text-slate-600">Update a lead.</p></div>
                    <div class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">DELETE</p><code class="mt-2 block text-sm font-semibold">/api/v1/leads/{lead}</code><p class="mt-3 text-sm text-slate-600">Delete a lead.</p></div>
                    <div class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">POST</p><code class="mt-2 block text-sm font-semibold">/api/v1/leads/{lead}/verify-email</code><p class="mt-3 text-sm text-slate-600">Verify lead email.</p></div>
                    <div class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">POST</p><code class="mt-2 block text-sm font-semibold">/api/v1/leads/{lead}/resend-verification</code><p class="mt-3 text-sm text-slate-600">Resend verification email.</p></div>
                    <div class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">GET</p><code class="mt-2 block text-sm font-semibold">/api/v1/leads/check-duplicate</code><p class="mt-3 text-sm text-slate-600">Check duplicate lead by email or phone.</p></div>
                    <div class="rounded-2xl border border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">POST</p><code class="mt-2 block text-sm font-semibold">/api/v1/leads/{lead}/convert</code><p class="mt-3 text-sm text-slate-600">Convert a lead into a customer.</p></div>
                    <div class="rounded-2xl border border-slate-200 p-5 md:col-span-2 xl:col-span-3"><p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-700">GET</p><code class="mt-2 block text-sm font-semibold">/api/v1/leads/analytics/report</code><p class="mt-3 text-sm text-slate-600">Return lead analytics reporting data.</p></div>
                </div>

                <div class="mt-6 rounded-2xl border border-orange-200 bg-orange-50 p-5 text-sm leading-6 text-orange-900">
                    <p class="font-bold">Public lead endpoints</p>
                    <p class="mt-2">These endpoints are available without API key auth and use the public rate limiter: <code>/api/v1/leads/public</code>, <code>/api/v1/leads/{lead}/status</code>, and <code>/api/v1/leads/{lead}/verify-email</code>.</p>
                </div>
            </section>

            <section id="errors" class="mt-8 mb-8 rounded-3xl border border-white/70 bg-white/90 p-8 shadow-soft">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-brand/10 p-3 text-brand">06</div>
                    <h2 class="text-2xl font-extrabold text-ink">Responses & Errors</h2>
                </div>
                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-bold text-slate-900">200 / 201</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Successful responses use a consistent JSON structure with <code>success</code>, <code>message</code>, and optional <code>data</code>.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-bold text-slate-900">401</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Returned when the API key is missing, invalid, or expired.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="font-bold text-slate-900">404 / 422</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">404 is returned for missing customer/subscription records. 422 is returned when required query parameters are missing or invalid.</p>
                    </div>
                </div>
                <div class="mt-6 rounded-2xl bg-slate-950 p-4 text-slate-100">
<pre class="overflow-x-auto text-sm leading-7"><code>{
  "success": false,
  "message": "API key is required",
  "error": "Missing API key. Please provide your API key in the X-API-Key header or Authorization header."
}</code></pre>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
