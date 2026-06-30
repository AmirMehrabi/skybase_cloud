@php
    $direction = config('ui.direction', 'ltr');
    $language = config('ui.language', 'en');
    $isRtl = $direction === 'rtl';
    $isFarsi = $language === 'fa';
    $user = auth()->user();
    $brandingTenant = tenant() ?? $user?->tenant;
    $brandName = $brandingTenant?->brandName() ?? env('APP_NAME');
    $brandTagline = $brandingTenant?->brandTagline() ?? 'Complete ISP Management Platform';
    $navbarLogoUrl = $brandingTenant?->navbarLogoUrl() ?? asset('assets/images/logo/logo-black.png');
    $hasCustomNavbarLogo = (bool) ($brandingTenant?->brandingAssetUrl('company_logo_dark') ?? $brandingTenant?->brandingAssetUrl('company_logo'));
@endphp

<!DOCTYPE html>
<html lang="{{ $language === 'fa' ? 'fa' : 'en' }}" dir="{{ $direction }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Dashboard') - {{ $brandName }}</title>
        <link rel="icon" href="{{ $brandingTenant?->faviconUrl() ?? asset('favicon.ico') }}">

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
            [x-cloak] { display: none !important; }

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
<body class="bg-[#f6f1e8] text-slate-950" style="direction: {{ $direction }};">
    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-950/60 z-40 hidden lg:hidden"></div>
    
    <!-- Fixed Sidebar -->
    <aside id="sidebar" class="fixed top-0 {{ $isRtl ? 'right-0' : 'left-0' }} z-50 w-64 h-screen overflow-hidden bg-[#0d2f35] text-white shadow-[0_35px_90px_rgba(13,47,53,0.28)] transform {{ $isRtl ? 'translate-x-full lg:translate-x-0' : '-translate-x-full lg:translate-x-0' }} transition-transform duration-300">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_20%_15%,rgba(34,197,94,0.24),transparent_28%),radial-gradient(circle_at_80%_10%,rgba(245,158,11,0.18),transparent_30%),linear-gradient(135deg,#09252b_0%,#0d2f35_48%,#123f3d_100%)]"></div>
        <div class="absolute left-1/2 top-0 -z-10 h-72 w-72 -translate-x-1/2 rounded-full border border-white/10 bg-white/[0.03] blur-3xl"></div>
        <div class="h-full flex flex-col">
            <!-- Logo -->
            <div class="h-[60px] flex items-center px-6 border-b border-white/10 bg-white/[0.03] backdrop-blur-xl">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 text-white">
                    @if($hasCustomNavbarLogo)
                        <img src="{{ $navbarLogoUrl }}" class="max-h-9 max-w-14 shrink-0 object-contain py-2" alt="{{ $brandName }} logo">
                    @else
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15">
                            <img src="{{ $navbarLogoUrl }}" class="max-w-6 brightness-0 invert" alt="{{ $brandName }} logo mark">
                        </span>
                    @endif
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-base font-bold tracking-tight">{{ $brandName }}</span>
                        <span class="block truncate text-[10px] font-bold tracking-[0.08em] text-emerald-100/80">{{ $brandTagline }}</span>
                    </span>
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
        <header class="fixed top-0 h-[60px] bg-[#fffaf0]/90 border-b border-slate-900/10 shadow-sm backdrop-blur-xl z-30 {{ $isRtl ? 'right-0 left-0 lg:right-64' : 'right-0 left-0 lg:left-64' }}">
            <div class="h-full flex items-center justify-between px-6">
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-[#fbf7ed] hover:text-slate-950">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                
                <!-- Logo (Mobile) -->
                <div class="lg:hidden">
                    <a href="{{ route('dashboard') }}" class="flex max-w-48 items-center gap-2 text-lg font-semibold text-slate-950">
                        @if($hasCustomNavbarLogo)
                            <img src="{{ $navbarLogoUrl }}" class="max-h-8 max-w-12 shrink-0 object-contain" alt="{{ $brandName }} logo">
                        @else
                            <img src="{{ $navbarLogoUrl }}" class="max-h-7 max-w-7 shrink-0 object-contain" alt="{{ $brandName }} logo mark">
                        @endif
                        <span class="truncate">{{ $brandName }}</span>
                    </a>
                </div>
                
                <!-- Search Input (Centered) -->
                <div class="hidden md:flex flex-1 max-w-md mx-8 relative z-50 mr-64">
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 {{ $isRtl ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="search-input" class="block w-full {{ $isRtl ? 'pr-10 pl-3' : 'pl-10 pr-3' }} py-2 border border-slate-900/10 bg-white/80 rounded-lg text-sm text-slate-900 placeholder-slate-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-[#0d2f35]/30" placeholder="Search resources..." autocomplete="off">
                        
                        <!-- Search Dropdown -->
                        <div id="search-dropdown" class="hidden absolute {{ $isRtl ? 'right-0' : 'left-0' }} mt-2 w-full bg-white rounded-xl shadow-xl border border-slate-900/10 max-h-96 overflow-y-auto z-50">
                            <!-- Loading State -->
                            <div id="search-loading" class="hidden px-4 py-3 text-sm text-slate-500">
                                <div class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Searching...</span>
                                </div>
                            </div>
                            
                            <!-- Recent Searches (shown when input is empty) -->
                            <div id="recent-searches" class="hidden">
                                <div class="px-4 py-2 border-b border-slate-900/10 flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-500 uppercase">Recent Searches</span>
                                    <button id="clear-recent-searches" class="text-xs text-slate-400 hover:text-slate-700">Clear</button>
                                </div>
                                <div id="recent-searches-list" class="py-1">
                                    <!-- Recent searches will be populated here -->
                                </div>
                                <div id="no-recent-searches" class="hidden px-4 py-3 text-sm text-slate-500 text-center">
                                    No recent searches
                                </div>
                            </div>
                            
                            <!-- Search Results -->
                            <div id="search-results" class="hidden">
                                <div id="modules-list" class="py-1"></div>
                                
                                <!-- No Results -->
                                <div id="no-results" class="hidden px-4 py-3 text-sm text-slate-500 text-center">
                                    No results found
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side: Notifications + User Menu -->
                <div class="flex items-center gap-4">
                    <x-notifications.dropdown />
                    
                    <!-- User Menu -->
                    <div class="relative">
                        <button id="user-menu-button" class="flex items-center gap-2 p-2 rounded-lg text-slate-700 hover:bg-[#fbf7ed] ">
                            <div class="w-8 h-8 rounded-full bg-[#0d2f35] flex items-center justify-center text-white text-sm font-medium shadow-[0_10px_24px_rgba(13,47,53,0.18)]">
                                @yield('user_initials', strtoupper(substr($user->name ?? 'A', 0, 2)))
                            </div>
                            <svg class="w-4 h-4 text-slate-500 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- User Dropdown Menu -->
                        <div id="user-menu" class="hidden absolute {{ $isRtl ? 'left-0' : 'right-0' }} mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-900/10 py-1 z-50">
                            <a href="{{ route('profile.index') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-[#fbf7ed]">Profile</a>
                            <div class="border-t border-slate-900/10 my-1"></div>
                            <form method="POST" action="{{ route('auth.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-[#fbf7ed]">Sign Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="min-h-screen pt-[75px] px-6 md:px-12 pb-8">
            <div class="space-y-3 mb-4">
                <x-ui.alert type="success" :message="session('success')" />
                <x-ui.alert type="error" :message="session('error')" />
            </div>
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
            const modulesList = document.getElementById('modules-list');
            const noResults = document.getElementById('no-results');
            const clearRecentSearchesBtn = document.getElementById('clear-recent-searches');
            
            const RECENT_SEARCHES_KEY = 'admin_recent_searches';
            const MAX_RECENT_SEARCHES = 10;
            const DEBOUNCE_DELAY = 400;
            
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
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-[#fbf7ed] focus:bg-[#fbf7ed] focus:outline-none recent-search-item" data-query="${escapeHtml(query)}" data-index="${index}">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                modulesList.innerHTML = '';
                noResults.classList.add('hidden');
                
                // Make request
                const url = new URL('{{ route('search.resources') }}', window.location.origin);
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
                
                const modules = data.modules || [];
                currentResults = [];
                selectedIndex = -1;

                if (modules.length === 0) {
                    modulesList.innerHTML = '';
                    noResults.classList.remove('hidden');

                    return;
                }

                noResults.classList.add('hidden');
                modulesList.innerHTML = modules.map((module) => {
                    const rows = (module.items || []).map((item) => {
                        const resultIndex = currentResults.length;
                        currentResults.push({ type: module.key, url: item.url });
                        const metaHtml = (item.meta || [])
                            .map((entry) => `<div class="text-xs text-slate-500 truncate">${safeHtml(entry)}</div>`)
                            .join('');

                        return `
                            <a href="${escapeHtml(item.url)}" class="block px-4 py-3 hover:bg-[#fbf7ed] focus:bg-[#fbf7ed] focus:outline-none search-result-item" data-index="${resultIndex}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[11px] uppercase tracking-wide text-slate-500 mb-1">${escapeHtml(module.label)}</div>
                                        <div class="text-sm font-medium text-slate-900 truncate">${safeHtml(item.title || '')}</div>
                                        ${metaHtml}
                                    </div>
                                    ${item.status ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">${escapeHtml(item.status)}</span>` : ''}
                                </div>
                            </a>
                        `;
                    }).join('');

                    return `
                        <div class="border-b border-slate-900/10 last:border-b-0">
                            <div class="px-4 py-2 bg-[#fbf7ed]">
                                <span class="text-xs font-semibold text-slate-700 uppercase">${escapeHtml(module.label)}</span>
                            </div>
                            <div class="py-1">${rows}</div>
                        </div>
                    `;
                }).join('');
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
                modulesList.innerHTML = '';
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
                        item.classList.add('bg-[#fbf7ed]');
                        item.focus();
                    } else {
                        item.classList.remove('bg-[#fbf7ed]');
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
