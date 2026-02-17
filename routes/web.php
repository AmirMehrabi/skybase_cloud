<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PlanController;
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
