<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;

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
