<?php

use App\Http\Controllers\Admin\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\Admin\Tenant\UserController;
use App\Http\Controllers\Auth\TenantLoginController;
use App\Http\Controllers\Auth\TenantRegistrationController;
use App\Http\Controllers\Billing\CreditController;
use App\Http\Controllers\Billing\DashboardController as BillingDashboardController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Billing\ReportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoRequestController;
use App\Http\Controllers\IpamController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ReportController as GeneralReportController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VpnUserController;
use Illuminate\Support\Facades\Route;

// Landing page - redirect authenticated users to dashboard
Route::get('/', [PagesController::class, 'index'])->name('home');

// Pricing page
Route::get('/pricing', [PagesController::class, 'pricing'])->name('pricing');

// Features page
Route::get('/features', [PagesController::class, 'features'])->name('features');

// Changelog page
Route::get('/changelog', [PagesController::class, 'changelog'])->name('changelog');

// Government brochure
Route::get('/brochure/government-fa', [PagesController::class, 'governmentBrochure'])->name('brochures.government-fa');

// Contact page
Route::get('/contact', [PagesController::class, 'contact'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::post('/demo-requests', [DemoRequestController::class, 'store'])->name('demo-requests.store');

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
        Route::put('/email', [SettingController::class, 'updateEmail'])->name('update.email');
        Route::post('/email/test', [SettingController::class, 'testEmail'])->name('test.email');
        Route::put('/ldap', [SettingController::class, 'updateLdap'])->name('update.ldap');
        Route::post('/ldap/test', [SettingController::class, 'testLdap'])->name('test.ldap');
        Route::post('/ldap/preview', [SettingController::class, 'previewLdap'])->name('preview.ldap');
        Route::post('/ldap/sync', [SettingController::class, 'syncLdap'])->name('sync.ldap');
        Route::delete('/assets/{asset}', [SettingController::class, 'deleteAsset'])->name('delete.asset');
    });

    // Customer Management Routes
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->name('index');
        Route::get('/data', [OrganizationController::class, 'data'])->name('data');
        Route::get('/stats', [OrganizationController::class, 'stats'])->name('stats');
        Route::get('/create', [OrganizationController::class, 'create'])->name('create');
        Route::post('/', [OrganizationController::class, 'store'])->name('store');
        Route::get('/{organization}', [OrganizationController::class, 'show'])->name('show');
        Route::get('/{organization}/edit', [OrganizationController::class, 'edit'])->name('edit');
        Route::put('/{organization}', [OrganizationController::class, 'update'])->name('update');
        Route::delete('/{organization}', [OrganizationController::class, 'destroy'])->name('destroy');
    });

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
        Route::patch('/{customer}/billing', [CustomerController::class, 'updateBilling'])->name('billing.update');
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
        Route::patch('/{subscription}/billing', [SubscriptionController::class, 'updateBilling'])->name('billing.update');
        Route::post('/{subscription}/suspend', [SubscriptionController::class, 'suspend'])->name('suspend');
        Route::post('/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('activate');
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/generate-invoice', [SubscriptionController::class, 'generateInvoice'])->name('generate-invoice');
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
    Route::get('/vpn-users/data', [VpnUserController::class, 'data'])->name('vpn-users.data');
    Route::get('/vpn-users/stats', [VpnUserController::class, 'stats'])->name('vpn-users.stats');
    Route::resource('vpn-users', VpnUserController::class);

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
        Route::post('/{router}/netflow/setup', [RouterController::class, 'setupNetflow'])->name('netflow.setup');
        Route::post('/{router}/netflow/test', [RouterController::class, 'testNetflow'])->name('netflow.test');
        Route::get('/{router}/netflow/data', [RouterController::class, 'netflowData'])->name('netflow.data');
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
        Route::get('/dashboard', [BillingDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::post('/generate-recurring', [InvoiceController::class, 'generateRecurring'])->name('generate-recurring');
            Route::get('/create', fn () => view('billing.invoices.create'))->name('create');
            Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
            Route::get('/{invoice}/edit', fn ($invoice) => view('billing.invoices.edit', compact('invoice')))->name('edit');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::post('/', [PaymentController::class, 'store'])->name('store');
            Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        });

        Route::get('/credits', [CreditController::class, 'index'])->name('credits');
        Route::post('/credits', [CreditController::class, 'store'])->name('credits.store');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    });

    // Network Routes
    Route::prefix('network')->name('network.')->group(function () {
        Route::get('/data-usage', [NetworkController::class, 'dataUsage'])->name('data-usage');
        Route::get('/bandwidth', [NetworkController::class, 'bandwidth'])->name('bandwidth');
        Route::get('/status', [NetworkController::class, 'status'])->name('status');
    });

    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/usage', [GeneralReportController::class, 'usage'])->name('usage');
        Route::get('/financial', [GeneralReportController::class, 'financial'])->name('financial');
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
