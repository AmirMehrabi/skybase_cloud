<?php

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
    Route::get('/', \App\Livewire\CustomersIndex::class)->name('index');
    Route::get('/create', \App\Livewire\CustomersCreate::class)->name('create');
    Route::get('/{id}', \App\Livewire\CustomersShow::class)->name('show');
    Route::get('/{id}/edit', \App\Livewire\CustomersEdit::class)->name('edit');
});
