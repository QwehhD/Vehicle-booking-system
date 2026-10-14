<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Auth\Access\AuthorizationException;

test('re-checks the status after locking before cancelling', function () {
    $admin = User::factory()->admin()->create();
    $booking = Booking::factory()->approved()->create();

    // Booking lain sempat menyelesaikannya setelah policy di route lolos.
    Booking::whereKey($booking->id)->update(['status' => BookingStatus::Completed]);

    expect(fn () => app(BookingService::class)->cancel($admin, $booking, 'Tidak jadi.'))
        ->toThrow(AuthorizationException::class);

    expect($booking->refresh())
        ->status->toBe(BookingStatus::Completed)
        ->cancel_reason->toBeNull();
});

test('re-checks the status after locking before completing', function () {
    $admin = User::factory()->admin()->create();
    $booking = Booking::factory()->approved()->create();

    Booking::whereKey($booking->id)->update(['status' => BookingStatus::Cancelled]);

    expect(fn () => app(BookingService::class)->complete($admin, $booking, [
        'odometer_start' => 1_000,
        'odometer_end' => 1_100,
        'fuel_used' => 10,
    ]))->toThrow(AuthorizationException::class);

    expect($booking->refresh())
        ->status->toBe(BookingStatus::Cancelled)
        ->odometer_end->toBeNull();
});
