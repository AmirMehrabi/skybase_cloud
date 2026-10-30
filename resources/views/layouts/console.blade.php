<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Console') · SkyBase</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}.console-content table th{padding:16px;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.05em;background:#f8faf9}.console-content table td{padding:18px 16px;vertical-align:top}.console-content table tbody tr:hover{background:#f8faf9}.console-content h2{color:#0d2f35}.console-content input,.console-content select,.console-content textarea{font-size:14px}</style>
</head>
<body class="min-h-screen bg-[#f6f1e8] text-slate-900 antialiased">
<header class="border-b border-white/10 bg-[#0d2f35] text-white"><div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-5 sm:px-8">
<a class="text-xl font-bold tracking-tight" href="{{ route('admin.super-admin.tenants.index') }}">SkyBase <span class="ml-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-teal-100">Owner console</span></a>
@auth<nav class="flex items-center gap-5 text-sm" aria-label="Console navigation"><a href="{{ route('admin.super-admin.tenants.index') }}" class="text-white hover:text-amber-200">ISP accounts</a><a href="{{ route('mfa.setup') }}" class="text-teal-100 hover:text-white">Security</a><form method="POST" action="{{ route('auth.logout') }}">@csrf<button class="rounded-lg border border-white/20 px-3 py-2 hover:bg-white/10">Sign out</button></form></nav>@endauth
</div></header>
<main class="console-content mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-10">
@if(session('success'))<div role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-900">{{ session('success') }}</div>@endif
@if($errors->any())<div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-900"><p class="font-semibold">Please review the following</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main><footer class="mx-auto max-w-7xl px-8 pb-8 text-xs text-slate-500">SkyBase administration · {{ auth()->user()?->email }}</footer>
</body></html>
