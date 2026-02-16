@php
    $direction = config('ui.direction', 'ltr');
    $language = config('ui.language', 'en');
    $isRtl = $direction === 'rtl';
    $isFarsi = $language === 'fa';
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="{{ $language === 'fa' ? 'fa' : 'en' }}" dir="{{ $direction }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Dashboard') - {{ config('app.name', 'SkyBill') }}</title>

        <!-- Fonts removed - using system fonts -->

        <!-- Pelak Font Face Declarations (Local) -->
        @if($isFarsi)
        <style>
            @font-face {
                font-family: 'Pelak';
                src: url('{{ asset('assets/fonts/pelak.woff2') }}') format('woff2'),
                     url('{{ asset('assets/fonts/pelak.woff') }}') format('woff');
                font-weight: 400;
                font-style: normal;
                font-display: swap;
            }
            
            @font-face {
                font-family: 'Pelak';
                src: url('{{ asset('assets/fonts/pelak.woff2') }}') format('woff2'),
                     url('{{ asset('assets/fonts/pelak.woff') }}') format('woff');
                font-weight: 500;
                font-style: normal;
                font-display: swap;
            }
            
            @font-face {
                font-family: 'Pelak';
                src: url('{{ asset('assets/fonts/pelak.woff2') }}') format('woff2'),
                     url('{{ asset('assets/fonts/pelak.woff') }}') format('woff');
                font-weight: 600;
                font-style: normal;
                font-display: swap;
            }
            
            @font-face {
                font-family: 'Pelak';
                src: url('{{ asset('assets/fonts/pelak.woff2') }}') format('woff2'),
                     url('{{ asset('assets/fonts/pelak.woff') }}') format('woff');
                font-weight: 700;
                font-style: normal;
                font-display: swap;
            }
            
            body, * {
                font-family: 'Pelak', 'Tahoma', 'Arial', sans-serif !important;
            }
        </style>
        @endif

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
            /* Tailwind CSS will be injected here */
            </style>
        @endif

        <style>
            /* Hide scrollbar for sidebar navigation */
            #sidebar nav {
                scrollbar-width: thin;
                scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
            }
            
            /* Webkit browsers (Chrome, Safari, Edge) */
            #sidebar nav::-webkit-scrollbar {
                width: 6px;
            }
            
            #sidebar nav::-webkit-scrollbar-track {
                background: transparent;
            }
            
            #sidebar nav::-webkit-scrollbar-thumb {
                background-color: rgba(255, 255, 255, 0.3);
                border-radius: 3px;
            }
            
            #sidebar nav::-webkit-scrollbar-thumb:hover {
                background-color: rgba(255, 255, 255, 0.5);
            }
        </style>
        
        @stack('styles')
    </head>
<body class="bg-white" style="direction: {{ $direction }};">
    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden lg:hidden"></div>
    
    <!-- Fixed Sidebar -->
    <aside id="sidebar" class="fixed top-0 {{ $isRtl ? 'right-0' : 'left-0' }} z-50 w-64 h-screen {{ request()->routeIs('admin.reports.*') ? 'bg-blue-900' : 'bg-blue-600' }} transform {{ $isRtl ? 'translate-x-full lg:translate-x-0' : '-translate-x-full lg:translate-x-0' }} transition-transform duration-300">
        <div class="h-full flex flex-col">
            <!-- Logo -->
            <div class="h-[60px] flex items-center px-6 border-b border-blue-700">
                <a href="{{ route('admin.dashboard') }}" class="text-xl font-semibold text-white">
                    {{ config('app.name', 'SkyBill') }}
                </a>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-6 px-4">
                @include('admin.partials.sidebar')
            </nav>
        </div>
    </aside>
    
    <!-- Main Content Area -->
    <div id="main-content-wrapper" class="{{ $isRtl ? 'pr-0 lg:pr-64' : 'pl-0 lg:pl-64' }}">
        <!-- Top Navigation Bar -->
        <header class="fixed top-0 h-[60px] bg-white border-b border-gray-200 z-30 {{ $isRtl ? 'right-0 left-0 lg:right-64' : 'right-0 left-0 lg:left-64' }}">
            <div class="h-full flex items-center justify-between px-6">
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                
                <!-- Logo (Mobile) -->
                <div class="lg:hidden">
                    <a href="{{ route('admin.dashboard') }}" class="text-lg font-semibold text-gray-900">
                        {{ config('app.name', 'SkyBill') }}
                    </a>
                </div>
                
                <!-- Search Input (Centered) -->
                <div class="hidden md:flex flex-1 max-w-md mx-8 relative z-50 mr-64">
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 {{ $isRtl ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="search-input" class="block w-full {{ $isRtl ? 'pr-10 pl-3' : 'pl-10 pr-3' }} py-2 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Search resources..." autocomplete="off">
                        
                        <!-- Search Dropdown -->
                        <div id="search-dropdown" class="hidden absolute {{ $isRtl ? 'right-0' : 'left-0' }} mt-2 w-full bg-white rounded-lg shadow-lg border border-gray-200 max-h-96 overflow-y-auto z-50">
                            <!-- Loading State -->
                            <div id="search-loading" class="hidden px-4 py-3 text-sm text-gray-500">
                                <div class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Searching...</span>
                                </div>
                            </div>
                            
                            <!-- Recent Searches (shown when input is empty) -->
                            <div id="recent-searches" class="hidden">
                                <div class="px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500 uppercase">Recent Searches</span>
                                    <button id="clear-recent-searches" class="text-xs text-gray-400 hover:text-gray-600">Clear</button>
                                </div>
                                <div id="recent-searches-list" class="py-1">
                                    <!-- Recent searches will be populated here -->
                                </div>
                                <div id="no-recent-searches" class="hidden px-4 py-3 text-sm text-gray-500 text-center">
                                    No recent searches
                                </div>
                            </div>
                            
                            <!-- Search Results -->
                            <div id="search-results" class="hidden">
                                <!-- Customers Section -->
                                <div id="customers-section" class="hidden">
                                    <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
                                        <span class="text-xs font-semibold text-gray-700 uppercase">Customers</span>
                                    </div>
                                    <div id="customers-list" class="py-1">
                                        <!-- Customer results will be populated here -->
                                    </div>
                                </div>
                                
                                <!-- Instances Section -->
                                <div id="instances-section" class="hidden">
                                    <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
                                        <span class="text-xs font-semibold text-gray-700 uppercase">Instances</span>
                                    </div>
                                    <div id="instances-list" class="py-1">
                                        <!-- Instance results will be populated here -->
                                    </div>
                                </div>
                                
                                <!-- No Results -->
                                <div id="no-results" class="hidden px-4 py-3 text-sm text-gray-500 text-center">
                                    No results found
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side: Notifications + User Menu -->
                <div class="flex items-center gap-4">
                    <!-- Notifications -->
                    <button class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span class="absolute top-1 {{ $isRtl ? 'left-1' : 'right-1' }} block h-2 w-2 rounded-full bg-blue-600 ring-2 ring-white"></span>
                    </button>
                    
                    <!-- User Menu -->
                    <div class="relative">
                        <button id="user-menu-button" class="flex items-center gap-2 p-2 rounded-lg text-gray-700 hover:bg-gray-100 ">
                            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-medium">
                                @yield('user_initials', strtoupper(substr($user->name ?? 'A', 0, 2)))
                            </div>
                            <svg class="w-4 h-4 text-gray-500 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- User Dropdown Menu -->
                        <div id="user-menu" class="hidden absolute {{ $isRtl ? 'left-0' : 'right-0' }} mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Settings</a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Sign Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="pt-[75px] px-6 md:px-12 pb-8">
            @yield('content')
        </main>
    </div>

    <!-- Custom JavaScript for Mobile Menu -->
    <script>
        // Check if RTL
        const isRtl = document.documentElement.dir === 'rtl' || document.body.style.direction === 'rtl';
        const translateClass = isRtl ? 'translate-x-full' : '-translate-x-full';
        
        // Mobile sidebar toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        
        function toggleSidebar() {
            // Only toggle on mobile screens (below lg breakpoint)
            if (window.innerWidth < 1024) {
                sidebar.classList.toggle(translateClass);
                sidebarOverlay.classList.toggle('hidden');
            }
        }
        
        // Ensure sidebar is visible on desktop on page load
        const mainContentWrapper = document.getElementById('main-content-wrapper');
        
        function checkSidebarVisibility() {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove(translateClass);
                sidebarOverlay.classList.add('hidden');
                // Ensure main content has proper padding (256px = w-64)
                if (mainContentWrapper) {
                    if (isRtl) {
                        mainContentWrapper.style.paddingRight = '256px';
                        mainContentWrapper.style.paddingLeft = '0';
                    } else {
                        mainContentWrapper.style.paddingLeft = '256px';
                        mainContentWrapper.style.paddingRight = '0';
                    }
                }
            } else {
                sidebar.classList.add(translateClass);
                // Remove padding on mobile
                if (mainContentWrapper) {
                    mainContentWrapper.style.paddingLeft = '0';
                    mainContentWrapper.style.paddingRight = '0';
                }
            }
        }
        
        // Check on load and resize
        checkSidebarVisibility();
        window.addEventListener('resize', checkSidebarVisibility);
        
        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', toggleSidebar);
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }
        
        // User menu toggle
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        
        if (userMenuButton && userMenu) {
            userMenuButton.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenu.classList.toggle('hidden');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        }

        // Search functionality
        (function() {
            const searchInput = document.getElementById('search-input');
            const searchDropdown = document.getElementById('search-dropdown');
            const searchLoading = document.getElementById('search-loading');
            const recentSearches = document.getElementById('recent-searches');
            const recentSearchesList = document.getElementById('recent-searches-list');
            const noRecentSearches = document.getElementById('no-recent-searches');
            const searchResults = document.getElementById('search-results');
            const customersSection = document.getElementById('customers-section');
            const customersList = document.getElementById('customers-list');
            const instancesSection = document.getElementById('instances-section');
            const instancesList = document.getElementById('instances-list');
            const noResults = document.getElementById('no-results');
            const clearRecentSearchesBtn = document.getElementById('clear-recent-searches');
            
            const RECENT_SEARCHES_KEY = 'admin_recent_searches';
            const MAX_RECENT_SEARCHES = 10;
            const DEBOUNCE_DELAY = 300;
            
            let searchTimeout = null;
            let currentRequest = null;
            let selectedIndex = -1;
            let currentResults = [];
            
            // Get recent searches from localStorage
            function getRecentSearches() {
                try {
                    const stored = localStorage.getItem(RECENT_SEARCHES_KEY);
                    return stored ? JSON.parse(stored) : [];
                } catch (e) {
                    return [];
                }
            }
            
            // Save recent search
            function saveRecentSearch(query) {
                if (!query || query.trim().length === 0) return;
                
                const recent = getRecentSearches();
                const trimmedQuery = query.trim();
                
                // Remove if already exists
                const index = recent.indexOf(trimmedQuery);
                if (index > -1) {
                    recent.splice(index, 1);
                }
                
                // Add to beginning
                recent.unshift(trimmedQuery);
                
                // Limit to max
                if (recent.length > MAX_RECENT_SEARCHES) {
                    recent.pop();
                }
                
                try {
                    localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(recent));
                } catch (e) {
                    console.error('Failed to save recent search:', e);
                }
            }
            
            // Clear recent searches
            function clearRecentSearches() {
                try {
                    localStorage.removeItem(RECENT_SEARCHES_KEY);
                    renderRecentSearches();
                } catch (e) {
                    console.error('Failed to clear recent searches:', e);
                }
            }
            
            // Render recent searches
            function renderRecentSearches() {
                const recent = getRecentSearches();
                
                if (recent.length === 0) {
                    noRecentSearches.classList.remove('hidden');
                    recentSearchesList.innerHTML = '';
                } else {
                    noRecentSearches.classList.add('hidden');
                    recentSearchesList.innerHTML = recent.map((query, index) => `
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none recent-search-item" data-query="${escapeHtml(query)}" data-index="${index}">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>${escapeHtml(query)}</span>
                            </div>
                        </button>
                    `).join('');
                    
                    // Add click handlers
                    recentSearchesList.querySelectorAll('.recent-search-item').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const query = btn.getAttribute('data-query');
                            searchInput.value = query;
                            performSearch(query);
                        });
                    });
                }
            }
            
            // Escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Safely insert HTML (for highlighted text from backend)
            // The backend provides HTML with <mark> tags, we sanitize it and add styling
            function safeHtml(html) {
                if (!html) return '';
                
                // Use a simple approach: parse with DOMParser, but be careful about duplication
                const parser = new DOMParser();
                const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html');
                const container = doc.body.firstElementChild;
                
                if (!container) {
                    // Fallback: escape everything
                    return escapeHtml(html);
                }
                
                // Remove dangerous elements
                container.querySelectorAll('script, iframe, object, embed, form, input, style').forEach(el => el.remove());
                
                // Add classes to mark tags using a simple replace
                // This avoids any text content duplication
                let result = container.innerHTML;
                
                // Replace <mark> with <mark class="...">
                result = result.replace(/<mark(\s|>)/gi, '<mark class="bg-yellow-200 font-medium"$1');
                
                return result;
            }
            
            // Perform search
            function performSearch(query) {
                if (!query || query.trim().length === 0) {
                    showRecentSearches();
                    return;
                }
                
                // Cancel previous request
                if (currentRequest) {
                    currentRequest.abort();
                }
                
                // Show loading
                searchLoading.classList.remove('hidden');
                recentSearches.classList.add('hidden');
                searchResults.classList.add('hidden');
                customersSection.classList.add('hidden');
                instancesSection.classList.add('hidden');
                noResults.classList.add('hidden');
                
                // Make request
                const url = new URL('#', window.location.origin);
                url.searchParams.append('q', query);
                
                const xhr = new XMLHttpRequest();
                currentRequest = xhr;
                
                xhr.open('GET', url.toString());
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            displayResults(data, query);
                            saveRecentSearch(query);
                        } catch (e) {
                            console.error('Failed to parse search results:', e);
                            showError();
                        }
                    } else {
                        showError();
                    }
                    searchLoading.classList.add('hidden');
                    currentRequest = null;
                };
                
                xhr.onerror = function() {
                    showError();
                    searchLoading.classList.add('hidden');
                    currentRequest = null;
                };
                
                xhr.send();
            }
            
            // Display search results
            function displayResults(data, query) {
                searchLoading.classList.add('hidden');
                recentSearches.classList.add('hidden');
                searchResults.classList.remove('hidden');
                
                const customers = data.customers || [];
                const instances = data.instances || [];
                currentResults = [];
                selectedIndex = -1;
                
                // Display customers
                if (customers.length > 0) {
                    customersSection.classList.remove('hidden');
                    customersList.innerHTML = customers.map((customer, index) => {
                        const resultIndex = currentResults.length;
                        currentResults.push({ type: 'customer', url: customer.url });
                        return `
                            <a href="${escapeHtml(customer.url)}" class="block px-4 py-3 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none search-result-item" data-index="${resultIndex}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900">${safeHtml(customer.name)}</div>
                                        ${customer.email ? `<div class="text-xs text-gray-500 mt-1">${safeHtml(customer.email)}</div>` : ''}
                                        ${customer.phone ? `<div class="text-xs text-gray-500">${safeHtml(customer.phone)}</div>` : ''}
                                        ${customer.company ? `<div class="text-xs text-gray-500">${safeHtml(customer.company)}</div>` : ''}
                                    </div>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                                        customer.status === 'active' ? 'bg-green-100 text-green-800' : 
                                        customer.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                        'bg-gray-100 text-gray-800'
                                    }">
                                        ${escapeHtml(customer.status)}
                                    </span>
                                </div>
                            </a>
                        `;
                    }).join('');
                } else {
                    customersSection.classList.add('hidden');
                }
                
                // Display instances
                if (instances.length > 0) {
                    instancesSection.classList.remove('hidden');
                    instancesList.innerHTML = instances.map((instance, index) => {
                        const resultIndex = currentResults.length;
                        currentResults.push({ type: 'instance', url: instance.url });
                        return `
                            <a href="${escapeHtml(instance.url)}" class="block px-4 py-3 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none search-result-item" data-index="${resultIndex}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900">${safeHtml(instance.name)}</div>
                                        ${instance.description ? `<div class="text-xs text-gray-500 mt-1">${safeHtml(instance.description)}</div>` : ''}
                                        ${instance.customer_name ? `<div class="text-xs text-gray-500">${safeHtml(instance.customer_name)}</div>` : ''}
                                        <div class="text-xs text-gray-500 mt-1">${escapeHtml(instance.region || '')}</div>
                                    </div>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                                        instance.status === 'active' ? 'bg-green-100 text-green-800' : 
                                        instance.status === 'building' ? 'bg-blue-100 text-blue-800' : 
                                        instance.status === 'error' ? 'bg-red-100 text-red-800' : 
                                        'bg-gray-100 text-gray-800'
                                    }">
                                        ${escapeHtml(instance.status)}
                                    </span>
                                </div>
                            </a>
                        `;
                    }).join('');
                } else {
                    instancesSection.classList.add('hidden');
                }
                
                // Show no results
                if (customers.length === 0 && instances.length === 0) {
                    noResults.classList.remove('hidden');
                } else {
                    noResults.classList.add('hidden');
                }
            }
            
            // Show recent searches
            function showRecentSearches() {
                searchLoading.classList.add('hidden');
                searchResults.classList.add('hidden');
                recentSearches.classList.remove('hidden');
                renderRecentSearches();
            }
            
            // Show error
            function showError() {
                searchLoading.classList.add('hidden');
                recentSearches.classList.add('hidden');
                searchResults.classList.remove('hidden');
                customersSection.classList.add('hidden');
                instancesSection.classList.add('hidden');
                noResults.classList.remove('hidden');
            }
            
            // Input event handler
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value;
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, DEBOUNCE_DELAY);
            });
            
            // Focus event handler
            searchInput.addEventListener('focus', function() {
                const query = this.value.trim();
                if (query.length === 0) {
                    showRecentSearches();
                }
                searchDropdown.classList.remove('hidden');
            });
            
            // Click outside to close
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.classList.add('hidden');
                    selectedIndex = -1;
                }
            });
            
            // Keyboard navigation
            searchInput.addEventListener('keydown', function(e) {
                if (!searchDropdown.classList.contains('hidden')) {
                    const items = searchDropdown.querySelectorAll('.search-result-item, .recent-search-item');
                    
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                        updateSelection(items);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        selectedIndex = Math.max(selectedIndex - 1, -1);
                        updateSelection(items);
                    } else if (e.key === 'Enter' && selectedIndex >= 0 && items[selectedIndex]) {
                        e.preventDefault();
                        items[selectedIndex].click();
                    } else if (e.key === 'Escape') {
                        searchDropdown.classList.add('hidden');
                        selectedIndex = -1;
                    }
                }
            });
            
            // Update selection
            function updateSelection(items) {
                items.forEach((item, index) => {
                    if (index === selectedIndex) {
                        item.classList.add('bg-gray-50');
                        item.focus();
                    } else {
                        item.classList.remove('bg-gray-50');
                    }
                });
            }
            
            // Clear recent searches
            if (clearRecentSearchesBtn) {
                clearRecentSearchesBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    clearRecentSearches();
                });
            }
        })();
    </script>

    <!-- Reports Sub-Sidebar JavaScript -->
    @if(request()->routeIs('admin.reports.*'))
    <script>
        (function() {
            const closeReportsSidebar = document.getElementById('close-reports-sidebar');
            const backToDashboard = document.getElementById('back-to-dashboard');
            
            // Close sub-sidebar handlers - navigate back to dashboard
            if (closeReportsSidebar) {
                closeReportsSidebar.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = '{{ route("admin.dashboard") }}';
                });
            }
            
            if (backToDashboard) {
                backToDashboard.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = '{{ route("admin.dashboard") }}';
                });
            }
        })();
    </script>
    @endif

    @stack('scripts')
</body>
</html>

