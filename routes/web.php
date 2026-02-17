<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RouterController;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    return view('home');
})->name('home');

// Main dashboard route
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('admin.dashboard');

// Admin logout route (placeholder - you may want to use Laravel's default authentication)
Route::post('/admin/logout', function () {
    // Placeholder for logout functionality
    // In production, use Laravel's built-in authentication
    return redirect('/');
})->name('admin.logout');

// Customer Management Routes
Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/create', [CustomerController::class, 'create'])->name('create');
    Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
});

// Subscription Management Routes
Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
    Route::get('/', function () {
        return view('subscriptions.index');
    })->name('index');

    Route::get('/create', function () {
        return view('subscriptions.create');
    })->name('create');

    Route::get('/{id}', function ($id) {
        return view('subscriptions.show', compact('id'));
    })->name('show');

    Route::get('/{id}/edit', function ($id) {
        return view('subscriptions.edit', compact('id'));
    })->name('edit');
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
    Route::get('/', fn() => view('ipam.dashboard'))->name('dashboard');

    Route::prefix('pools')->name('pools.')->group(function () {
        Route::get('/', fn() => view('ipam.pools.index'))->name('index');
        Route::get('/create', fn() => view('ipam.pools.create'))->name('create');
        Route::get('/{pool}', fn($pool) => view('ipam.pools.show', compact('pool')))->name('show');
        Route::get('/{pool}/edit', fn($pool) => view('ipam.pools.edit', compact('pool')))->name('edit');
    });

    Route::prefix('ips')->name('ips.')->group(function () {
        Route::get('/', fn() => view('ipam.ips.index'))->name('index');
        Route::get('/{ip}', fn($ip) => view('ipam.ips.show', compact('ip')))->name('show');
    });
});
