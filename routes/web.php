<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::middleware('auth')->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::inertia('bookings', 'bookings/index')->name('bookings.index');
        Route::inertia('reports', 'reports/index')->name('reports.index');
        Route::inertia('activity-logs', 'activity-logs/index')->name('activity-logs.index');
        Route::inertia('vehicles', 'vehicles/index')->name('vehicles.index');
    });

    Route::middleware('role:approver')->group(function () {
        Route::inertia('approvals', 'approvals/index')->name('approvals.index');
    });
});

require __DIR__.'/auth.php';
