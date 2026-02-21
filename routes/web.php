<?php

use App\Http\Controllers\Admin\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\Admin\Tenant\UserController;
use App\Http\Controllers\Auth\TenantLoginController;
use App\Http\Controllers\Auth\TenantRegistrationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RouterController;
use Illuminate\Support\Facades\Route;

// Landing page - redirect authenticated users to dashboard
Route::get('/', [PagesController::class, 'index'])->name('home');

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
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Tenant User Management
    Route::prefix('settings/users')->name('admin.tenant.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Customer Management Routes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
    });

    // Subscription Management Routes
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', fn () => view('subscriptions.index'))->name('index');
        Route::get('/create', fn () => view('subscriptions.create'))->name('create');
        Route::get('/{id}', fn ($id) => view('subscriptions.show', compact('id')))->name('show');
        Route::get('/{id}/edit', fn ($id) => view('subscriptions.edit', compact('id')))->name('edit');
    });

    // Plan Management Routes
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/create', [PlanController::class, 'create'])->name('create');
        Route::get('/{id}', [PlanController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [PlanController::class, 'edit'])->name('edit');
    });

    // Router Management Routes
    Route::prefix('routers')->name('routers.')->group(function () {
        Route::get('/', [RouterController::class, 'index'])->name('index');
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
        Route::get('/', fn () => view('ipam.dashboard'))->name('dashboard');

        Route::prefix('pools')->name('pools.')->group(function () {
            Route::get('/', fn () => view('ipam.pools.index'))->name('index');
            Route::get('/create', fn () => view('ipam.pools.create'))->name('create');
            Route::get('/{pool}', fn ($pool) => view('ipam.pools.show', compact('pool')))->name('show');
            Route::get('/{pool}/edit', fn ($pool) => view('ipam.pools.edit', compact('pool')))->name('edit');
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
