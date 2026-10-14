<?php

use App\Http\Controllers\BookingController;
use App\Models\Booking;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::middleware('auth')->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::get('bookings', [BookingController::class, 'index'])
            ->can('viewAny', Booking::class)
            ->name('bookings.index');
        Route::get('bookings/create', [BookingController::class, 'create'])
            ->can('create', Booking::class)
            ->name('bookings.create');
        Route::post('bookings', [BookingController::class, 'store'])
            ->can('create', Booking::class)
            ->name('bookings.store');
        Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])
            ->can('cancel', 'booking')
            ->name('bookings.cancel');
        Route::patch('bookings/{booking}/complete', [BookingController::class, 'complete'])
            ->can('complete', 'booking')
            ->name('bookings.complete');

        Route::inertia('reports', 'reports/index')->name('reports.index');
        Route::inertia('activity-logs', 'activity-logs/index')->name('activity-logs.index');
        Route::inertia('vehicles', 'vehicles/index')->name('vehicles.index');
    });

    Route::get('bookings/{booking}', [BookingController::class, 'show'])
        ->can('view', 'booking')
        ->name('bookings.show');

    Route::middleware('role:approver')->group(function () {
        Route::inertia('approvals', 'approvals/index')->name('approvals.index');
    });
});

require __DIR__.'/auth.php';
