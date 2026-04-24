<?php

use App\Http\Controllers\Admin\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\Admin\Tenant\UserController;
use App\Http\Controllers\Auth\TenantLoginController;
use App\Http\Controllers\Auth\TenantRegistrationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IpamController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Landing page - redirect authenticated users to dashboard
Route::get('/', [PagesController::class, 'index'])->name('home');

// Pricing page
Route::get('/pricing', [PagesController::class, 'pricing'])->name('pricing');

// Features page
Route::get('/features', [PagesController::class, 'features'])->name('features');

// Authentication Routes (Guest only)
Route::middleware(['guest'])->prefix('auth')->name('auth.')->group(function () {
    Route::get('/register', [TenantRegistrationController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [TenantRegistrationController::class, 'register'])->name('register.store');
    Route::get('/login', [TenantLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [TenantLoginController::class, 'login'])->name('login.store');
});

// Logout route (authenticated only)
Route::post('/auth/logout', [TenantLoginController::class, 'logout'])->name('auth.logout')->middleware('auth');

// Protected Routes (Require Authentication & Tenancy)
Route::middleware(['auth', 'initialize_tenancy', 'check_tenant_status'])->group(function () {

    // Dashboard
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Tenant User Management
    Route::prefix('settings/users')->name('admin.tenant.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/general', [SettingController::class, 'updateGeneral'])->name('update.general');
        Route::put('/branding', [SettingController::class, 'updateBranding'])->name('update.branding');
        Route::delete('/assets/{asset}', [SettingController::class, 'deleteAsset'])->name('delete.asset');
    });

    // Customer Management Routes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/data', [CustomerController::class, 'data'])->name('data');
        Route::get('/filter-options', [CustomerController::class, 'filterOptions'])->name('filter-options');
        Route::get('/stats', [CustomerController::class, 'stats'])->name('stats');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
        Route::post('/{customer}/suspend', [CustomerController::class, 'suspend'])->name('suspend');
        Route::post('/{customer}/activate', [CustomerController::class, 'activate'])->name('activate');
    });

    // Subscription Management Routes
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/data', [SubscriptionController::class, 'data'])->name('data');
        Route::get('/stats', [SubscriptionController::class, 'stats'])->name('stats');
        Route::get('/check-pppoe-username', [SubscriptionController::class, 'checkPppoeUsername'])->name('check-pppoe-username');
        Route::get('/create', [SubscriptionController::class, 'create'])->name('create');
        Route::post('/', [SubscriptionController::class, 'store'])->name('store');
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
        Route::get('/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('edit');
        Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
        Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])->name('destroy');
        Route::post('/{subscription}/suspend', [SubscriptionController::class, 'suspend'])->name('suspend');
        Route::post('/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('activate');
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
    });

    // Plan Management Routes
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/create', [PlanController::class, 'create'])->name('create');
        Route::post('/', [PlanController::class, 'store'])->name('store');
        Route::get('/{plan}', [PlanController::class, 'show'])->name('show');
        Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy');
    });

    // Router Management Routes
    Route::prefix('routers')->name('routers.')->group(function () {
        Route::get('/', [RouterController::class, 'index'])->name('index');
        Route::get('/data', [RouterController::class, 'data'])->name('data');
        Route::get('/filter-options', [RouterController::class, 'filterOptions'])->name('filter-options');
        Route::get('/stats', [RouterController::class, 'stats'])->name('stats');
        Route::get('/create', [RouterController::class, 'create'])->name('create');
        Route::post('/', [RouterController::class, 'store'])->name('store');
        Route::get('/{router}', [RouterController::class, 'show'])->name('show');
        Route::get('/{router}/edit', [RouterController::class, 'edit'])->name('edit');
        Route::put('/{router}', [RouterController::class, 'update'])->name('update');
        Route::delete('/{router}', [RouterController::class, 'destroy'])->name('destroy');
        Route::get('/{router}/sessions', [RouterController::class, 'sessions'])->name('sessions');
        Route::get('/{router}/queues', [RouterController::class, 'queues'])->name('queues');
        Route::get('/{router}/profiles', [RouterController::class, 'profiles'])->name('profiles');
        Route::get('/{router}/interfaces', [RouterController::class, 'interfaces'])->name('interfaces');
        Route::get('/{router}/ip-pools', [RouterController::class, 'ipPools'])->name('ip-pools');
        Route::get('/{router}/logs', [RouterController::class, 'logs'])->name('logs');
    });

    // IP Address Management (IPAM) Routes
    Route::prefix('ipam')->name('ipam.')->group(function () {
        Route::get('/', [IpamController::class, 'dashboard'])->name('dashboard');
        Route::get('/check-ip', [IpamController::class, 'checkIp'])->name('check-ip');

        Route::prefix('pools')->name('pools.')->group(function () {
            Route::get('/', [IpamController::class, 'index'])->name('index');
            Route::get('/create', [IpamController::class, 'create'])->name('create');
            Route::post('/', [IpamController::class, 'store'])->name('store');
            Route::get('/{pool}', [IpamController::class, 'show'])->name('show');
            Route::get('/{pool}/edit', [IpamController::class, 'edit'])->name('edit');
            Route::put('/{pool}', [IpamController::class, 'update'])->name('update');
            Route::delete('/{pool}', [IpamController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('ips')->name('ips.')->group(function () {
            Route::get('/', fn () => view('ipam.ips.index'))->name('index');
            Route::get('/{ip}', fn ($ip) => view('ipam.ips.show', compact('ip')))->name('show');
        });
    });

    // Billing Routes
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/dashboard', fn () => view('billing.dashboard'))->name('dashboard');

        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', fn () => view('billing.invoices.index'))->name('index');
            Route::get('/create', fn () => view('billing.invoices.create'))->name('create');
            Route::get('/{invoice}', fn ($invoice) => view('billing.invoices.show', compact('invoice')))->name('show');
            Route::get('/{invoice}/edit', fn ($invoice) => view('billing.invoices.edit', compact('invoice')))->name('edit');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', fn () => view('billing.payments.index'))->name('index');
            Route::get('/{payment}', fn ($payment) => view('billing.payments.show', compact('payment')))->name('show');
        });

        Route::get('/credits', fn () => view('billing.credits'))->name('credits');
        Route::get('/reports', fn () => view('billing.reports'))->name('reports');
    });

    // Network Routes
    Route::prefix('network')->name('network.')->group(function () {
        Route::get('/data-usage', fn () => view('network.data-usage'))->name('data-usage');
        Route::get('/bandwidth', fn () => view('network.bandwidth'))->name('bandwidth');
        Route::get('/status', fn () => view('network.status'))->name('status');
    });

    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/usage', fn () => view('reports.usage'))->name('usage');
        Route::get('/financial', fn () => view('reports.financial'))->name('financial');
    });
});

// Super Admin Routes (Separate from tenant routes)
Route::middleware(['auth'])->prefix('admin/super-admin')->name('admin.super-admin.')->group(function () {
    Route::get('/tenants', [SuperAdminTenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/{tenant}', [SuperAdminTenantController::class, 'show'])->name('tenants.show');
    Route::get('/tenants/{tenant}/edit', [SuperAdminTenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [SuperAdminTenantController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/suspend', [SuperAdminTenantController::class, 'suspend'])->name('tenants.suspend');
    Route::post('/tenants/{tenant}/activate', [SuperAdminTenantController::class, 'activate'])->name('tenants.activate');
    Route::delete('/tenants/{tenant}', [SuperAdminTenantController::class, 'destroy'])->name('tenants.destroy');
});
