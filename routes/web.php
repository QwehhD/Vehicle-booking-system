<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VehicleController;
use App\Models\Booking;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

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

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
    });

    Route::get('bookings/{booking}', [BookingController::class, 'show'])
        ->can('view', 'booking')
        ->name('bookings.show');

    Route::middleware('role:approver')->group(function () {
        Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('approvals/{approval}/approve', [ApprovalController::class, 'approve'])
            ->can('approve', 'approval')
            ->name('approvals.approve');
        Route::post('approvals/{approval}/reject', [ApprovalController::class, 'reject'])
            ->can('reject', 'approval')
            ->name('approvals.reject');
    });
});

require __DIR__.'/auth.php';
