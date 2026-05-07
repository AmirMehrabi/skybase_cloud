<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SkyBase Cloud') - ISP Management Platform</title>
                @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#f6f1e8] text-slate-950">
    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo -->
            <div class="text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#0d2f35] shadow-[0_16px_36px_rgba(13,47,53,0.22)]">
                        <i class="fas fa-cloud text-xl text-[#f5c542]"></i>
                    </div>
                    <span class="text-2xl font-bold text-slate-950">SkyBase Cloud</span>
                </a>
                <p class="mt-2 text-sm font-medium text-slate-600">Complete ISP Management Platform</p>
            </div>

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg" x-data="{ show: true }" x-show="show" x-transition>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                        <button @click="show = false" class="ml-auto"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg" x-data="{ show: true }" x-show="show" x-transition>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ session('error') }}</span>
                        <button @click="show = false" class="ml-auto"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg" x-data="{ show: true }" x-show="show" x-transition>
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button @click="show = false" class="ml-auto"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            @endif

            <!-- Content -->
            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
