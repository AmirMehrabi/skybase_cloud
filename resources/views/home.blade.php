<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="SkyBase Cloud - Cloud Control Plane for MikroTik ISPs. Manage authentication, monitor routers, and provision customers from one secure platform. The best ISP management software for WISPs.">
    <meta name="keywords" content="ISP management software, MikroTik management, Radius server, WISP software, ISP billing and monitoring, PPPoE management, MikroTik provisioning">
    <meta name="author" content="SkyBase Cloud">

    <!-- Open Graph -->
    <meta property="og:title" content="SkyBase Cloud - ISP Management Software for MikroTik Networks">
    <meta property="og:description" content="Cloud-based control plane for WISPs. Manage MikroTik routers, Radius authentication, and customer provisioning from one platform.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">

    <title>SkyBase Cloud - ISP Management Software for MikroTik Networks</title>

            @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body class="bg-white">
    <!-- Navbar -->
    <nav class="border-b border-gray-200 bg-white sticky top-0 z-50">
        <div class="container mx-auto">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="text-xl font-bold text-gray-900">SkyBase Cloud</a>
                </div>

                <!-- Center Links (Desktop) -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-gray-600 hover:text-gray-900 text-sm font-medium">Features</a>
                    <a href="#pricing" class="text-gray-600 hover:text-gray-900 text-sm font-medium">Pricing</a>
                    <a href="#docs" class="text-gray-600 hover:text-gray-900 text-sm font-medium">Docs</a>
                    <a href="#about" class="text-gray-600 hover:text-gray-900 text-sm font-medium">About</a>
                </div>

                <!-- Right Buttons -->
                <div class="flex items-center gap-3">
                    <a href="{{ url('/dashboard') }}" class="hidden sm:inline-flex text-sm font-medium text-gray-700 hover:text-gray-900 px-4 py-2">Login</a>
                    <a href="#trial" class="btn btn-primary text-sm">Start Free Trial</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="section bg-gray-50 border-b border-gray-200">
        <div class="container mx-auto">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 leading-tight mb-6">
                    Cloud Control Plane for MikroTik ISPs
                </h1>
                <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                    SkyBase Cloud helps WISPs and ISPs manage authentication, monitor routers, and provision customers from one secure, scalable platform. Replace manual MikroTik configuration and self-hosted Radius servers with a reliable cloud solution.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-8">
                    <a href="#trial" class="btn btn-primary w-full sm:w-auto">Start Free Trial</a>
                    <a href="#demo" class="btn btn-secondary w-full sm:w-auto">Book a Demo</a>
                </div>
                <p class="text-sm text-gray-500">
                    Built for real ISPs. Designed for MikroTik networks. Trusted globally.
                </p>
            </div>

            <!-- Dashboard Preview -->
            <div class="mt-16 bg-white border border-gray-200 rounded-lg shadow-sm p-4 max-w-5xl mx-auto">
                <div class="bg-gray-100 rounded aspect-video flex items-center justify-center">
                    <div class="text-center text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm">Dashboard Preview</p>
                        <p class="text-xs mt-1">Router Status · Online Users · Monitoring Graphs</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Problem Section -->
    <section class="section">
        <div class="container mx-auto">
            <div class="max-w-3xl mx-auto">
                <h2 class="text-3xl font-bold text-gray-900 text-center mb-6">
                    Running an ISP Should Not Be This Hard
                </h2>
                <p class="text-lg text-gray-600 text-center mb-12">
                    Most ISPs rely on fragile scripts, self-hosted Radius servers, and manual MikroTik configuration.
                </p>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-red-50 border border-red-100 rounded-lg p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">The Problems:</h3>
                        <ul class="space-y-3 text-gray-600">
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-1">✗</span>
                                <span>Network outages that go unnoticed</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-1">✗</span>
                                <span>Authentication failures</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-1">✗</span>
                                <span>Slow provisioning of new customers</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-1">✗</span>
                                <span>Lack of visibility across routers and towers</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-1">✗</span>
                                <span>Complex infrastructure that is hard to maintain</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-green-50 border border-green-100 rounded-lg p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">The Solution:</h3>
                        <p class="text-gray-600 leading-relaxed">
                            SkyBase Cloud solves these problems by providing a centralized, cloud-based control plane for your ISP.
                        </p>
                        <ul class="space-y-3 text-gray-600 mt-4">
                            <li class="flex items-start gap-2">
                                <span class="text-green-500 mt-1">✓</span>
                                <span>Instant alerts when devices fail</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-green-500 mt-1">✓</span>
                                <span>Reliable Radius authentication</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-green-500 mt-1">✓</span>
                                <span>Provision customers in seconds</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-green-500 mt-1">✓</span>
                                <span>Full network visibility</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-green-500 mt-1">✓</span>
                                <span>Zero infrastructure management</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section bg-gray-50">
        <div class="container mx-auto">
            <div class="text-center my-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">
                    Everything You Need to Operate Your ISP
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    A complete platform built specifically for MikroTik-based networks
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Centralized MikroTik Management</h3>
                    <p class="text-gray-600 mb-4">Manage all your MikroTik routers from one dashboard.</p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li>• Connect routers via MikroTik API</li>
                        <li>• Monitor router health and performance</li>
                        <li>• View traffic and uptime</li>
                        <li>• Manage hundreds of routers</li>
                    </ul>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Cloud Radius Authentication</h3>
                    <p class="text-gray-600 mb-4">Reliable PPPoE and Hotspot authentication without managing your own server.</p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li>• Secure customer authentication</li>
                        <li>• Session tracking and accounting</li>
                        <li>• Profile-based speed limits</li>
                        <li>• High availability architecture</li>
                    </ul>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Real-Time Monitoring</h3>
                    <p class="text-gray-600 mb-4">See exactly what is happening in your network.</p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li>• Router online/offline status</li>
                        <li>• CPU, memory, and traffic monitoring</li>
                        <li>• Instant alerts when devices go offline</li>
                        <li>• Historical metrics and graphs</li>
                    </ul>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Automated Provisioning</h3>
                    <p class="text-gray-600 mb-4">Provision new customers in seconds.</p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li>• Automatic Radius user creation</li>
                        <li>• Assign plans and profiles instantly</li>
                        <li>• Activate or suspend customers remotely</li>
                        <li>• Eliminate manual configuration errors</li>
                    </ul>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Multi-User Access Control</h3>
                    <p class="text-gray-600 mb-4">Allow your team to work securely.</p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li>• Owner, Admin, Support, Read-Only roles</li>
                        <li>• Secure tenant isolation</li>
                        <li>• Full audit logging</li>
                        <li>• Granular permissions</li>
                    </ul>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="w-12 h-12 bg-cyan-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">White-Label Ready</h3>
                    <p class="text-gray-600 mb-4">SkyBase Cloud adapts to your brand.</p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li>• Custom logo</li>
                        <li>• Custom domain</li>
                        <li>• Branded interface for your ISP</li>
                        <li>• Custom CSS support</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="section">
        <div class="container mx-auto">
            <div class="text-center my-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Built for Reliability and Scale</h2>
                <p class="text-lg text-gray-600">Why ISPs choose SkyBase Cloud</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Reduce Operational Complexity</h3>
                    <p class="text-gray-600 text-sm">Replace multiple tools with one unified platform.</p>
                </div>

                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Improve Network Visibility</h3>
                    <p class="text-gray-600 text-sm">Know immediately when devices fail or customers disconnect.</p>
                </div>

                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Save Engineering Time</h3>
                    <p class="text-gray-600 text-sm">Automate repetitive tasks and eliminate manual provisioning.</p>
                </div>

                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Scale Without Infrastructure Headaches</h3>
                    <p class="text-gray-600 text-sm">No need to manage servers, backups, or Radius infrastructure.</p>
                </div>

                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Increase Network Reliability</h3>
                    <p class="text-gray-600 text-sm">Detect and resolve problems faster.</p>
                </div>

                <div class="border-l-4 border-blue-600 bg-gray-50 rounded-r-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Lower Total Cost of Ownership</h3>
                    <p class="text-gray-600 text-sm">Pay for what you use. No upfront infrastructure costs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="section bg-gray-50">
        <div class="container mx-auto">
            <div class="text-center py-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Get Started in Minutes</h2>
                <p class="text-lg text-gray-600">Three simple steps to transform your ISP operations</p>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="flex flex-col md:flex-row gap-8">
                    <!-- Step 1 -->
                    <div class="flex-1">
                        <div class="bg-white border border-gray-200 rounded-lg p-6 h-full">
                            <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg mb-4">1</div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Create Your Account</h3>
                            <p class="text-gray-600 text-sm">Sign up and create your ISP workspace.</p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex-1">
                        <div class="bg-white border border-gray-200 rounded-lg p-6 h-full">
                            <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg mb-4">2</div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Connect Your Routers</h3>
                            <p class="text-gray-600 text-sm">Add MikroTik routers using API credentials.</p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex-1">
                        <div class="bg-white border border-gray-200 rounded-lg p-6 h-full">
                            <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg mb-4">3</div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Start Managing</h3>
                            <p class="text-gray-600 text-sm">Provision customers, monitor devices, and control your network.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Target Audience Section -->
    <section class="section">
        <div class="container mx-auto">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Designed for WISPs, ISPs, and Network Operators</h2>
                <p class="text-lg text-gray-600 mb-8">SkyBase Cloud is ideal for:</p>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="text-gray-900 font-medium text-sm">Wireless ISPs (WISPs)</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="text-gray-900 font-medium text-sm">Fiber ISPs</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="text-gray-900 font-medium text-sm">MikroTik Networks</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="text-gray-900 font-medium text-sm">Multi-Router Operators</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="text-gray-900 font-medium text-sm">Growing ISPs</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="text-gray-900 font-medium text-sm">MikroTik Hotspots</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Comparison Section -->
    <section class="section bg-gray-50">
        <div class="container mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Why Choose SkyBase Cloud</h2>
                <p class="text-lg text-gray-600">Unlike traditional ISP management software</p>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="grid md:grid-cols-5 gap-4 items-center mb-8">
                    <div class="md:col-span-1"></div>
                    <div class="md:col-span-2 bg-blue-600 text-white rounded-lg p-4 text-center font-semibold">
                        SkyBase Cloud
                    </div>
                    <div class="md:col-span-2 bg-gray-200 rounded-lg p-4 text-center font-semibold text-gray-700">
                        Traditional Solutions
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="grid md:grid-cols-5 gap-4 items-center">
                        <div class="font-medium text-gray-900 text-sm">Deployment</div>
                        <div class="md:col-span-2 text-center text-green-600 font-medium">Cloud-native</div>
                        <div class="md:col-span-2 text-center text-gray-500">Self-hosted</div>
                    </div>

                    <div class="grid md:grid-cols-5 gap-4 items-center">
                        <div class="font-medium text-gray-900 text-sm">Setup Time</div>
                        <div class="md:col-span-2 text-center text-green-600 font-medium">Minutes</div>
                        <div class="md:col-span-2 text-center text-gray-500">Days/Weeks</div>
                    </div>

                    <div class="grid md:grid-cols-5 gap-4 items-center">
                        <div class="font-medium text-gray-900 text-sm">Scalability</div>
                        <div class="md:col-span-2 text-center text-green-600 font-medium">Unlimited</div>
                        <div class="md:col-span-2 text-center text-gray-500">Limited by hardware</div>
                    </div>

                    <div class="grid md:grid-cols-5 gap-4 items-center">
                        <div class="font-medium text-gray-900 text-sm">MikroTik Focus</div>
                        <div class="md:col-span-2 text-center text-green-600 font-medium">Built for MikroTik</div>
                        <div class="md:col-span-2 text-center text-gray-500">Generic</div>
                    </div>

                    <div class="grid md:grid-cols-5 gap-4 items-center">
                        <div class="font-medium text-gray-900 text-sm">Maintenance</div>
                        <div class="md:col-span-2 text-center text-green-600 font-medium">We handle it</div>
                        <div class="md:col-span-2 text-center text-gray-500">You manage it</div>
                    </div>
                </div>

                <div class="mt-12 p-6 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-center text-gray-900 font-medium">No server installation required. No complex configuration. Just connect your routers and start managing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Preview Section -->
    <section id="pricing" class="section">
        <div class="container mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Simple, Transparent Pricing</h2>
                <p class="text-lg text-gray-600">Flexible pricing based on the size of your network</p>
                <p class="text-gray-500 mt-2">Start small and scale as you grow.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#trial" class="btn btn-primary">View Pricing</a>
                <a href="#trial" class="btn btn-secondary">Start Free Trial</a>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <section class="section bg-gray-50">
        <div class="container mx-auto">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Built for Real-World ISP Operations</h2>
                <p class="text-lg text-gray-600 leading-relaxed">
                    SkyBase Cloud is built by network engineers with real experience running ISP infrastructure. The platform is designed for reliability, security, and scalability.
                </p>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="section bg-blue-600">
        <div class="container mx-auto">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-white mb-4">Start Managing Your ISP the Modern Way</h2>
                <p class="text-xl text-blue-100 mb-8">Join ISPs using SkyBase Cloud to simplify operations and improve reliability.</p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="#trial" class="bg-white text-blue-600 hover:bg-gray-100 px-8 py-3 rounded-lg font-semibold transition-colors">Start Free Trial</a>
                    <a href="#demo" class="border-2 border-white text-white hover:bg-white hover:text-blue-600 px-8 py-3 rounded-lg font-semibold transition-colors">Book a Demo</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="container mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8 mb-8">
                <!-- Brand -->
                <div class="col-span-2">
                    <h3 class="text-white font-bold text-lg mb-4">SkyBase Cloud</h3>
                    <p class="text-sm">Cloud-based ISP management platform for MikroTik networks.</p>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="text-white font-semibold mb-3">Product</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="hover:text-white">Features</a></li>
                        <li><a href="#pricing" class="hover:text-white">Pricing</a></li>
                        <li><a href="#docs" class="hover:text-white">Documentation</a></li>
                        <li><a href="#" class="hover:text-white">Changelog</a></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h4 class="text-white font-semibold mb-3">Company</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#about" class="hover:text-white">About</a></li>
                        <li><a href="#" class="hover:text-white">Contact</a></li>
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                    </ul>
                </div>

                <!-- Resources -->
                <div>
                    <h4 class="text-white font-semibold mb-3">Resources</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">ISP Management Guide</a></li>
                        <li><a href="#" class="hover:text-white">MikroTik Integration</a></li>
                        <li><a href="#" class="hover:text-white">Radius Authentication</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contact & Bottom -->
            <div class="pt-8 border-t border-gray-800">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-sm">
                        <a href="mailto:support@skybase.cloud" class="hover:text-white">support@skybase.cloud</a>
                        <span class="mx-2">·</span>
                        <a href="https://skybase.cloud" class="hover:text-white">skybase.cloud</a>
                    </div>
                    <p class="text-sm">© 2026 SkyBase Cloud. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
