<?php

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Enums\VehicleOwnership;
use App\Enums\VehicleStatus;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Region;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->freezeTime();
});

test('seeds the demo accounts with the password "password"', function () {
    $this->seed(DatabaseSeeder::class);

    $users = User::with('location')->get()->keyBy('email');

    expect($users->keys()->sort()->values()->all())->toBe([
        'admin@nikel.test',
        'approver1@nikel.test',
        'approver2@nikel.test',
        'approver3@nikel.test',
    ]);

    expect($users['admin@nikel.test'])
        ->role->toBe(UserRole::Admin)
        ->name->toBe('Admin Pool')
        ->location->name->toBe('Kantor Pusat Kendari');

    expect($users['approver1@nikel.test'])->name->toBe('Budi Santoso')->position->toBe('Supervisor Pool');
    expect($users['approver2@nikel.test'])->name->toBe('Siti Rahmawati')->position->toBe('Manager Operasional');
    expect($users['approver3@nikel.test'])->name->toBe('Andi Pratama')->position->toBe('Kepala Cabang');

    $users->each(function (User $user) {
        expect(Hash::check('password', $user->password))->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull();
    });
});

test('seeds the regions, locations, drivers, and vehicles from the spec', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Region::pluck('name')->sort()->values()->all())
        ->toBe(['Maluku Utara', 'Sulawesi Tengah', 'Sulawesi Tenggara'])
        ->and(Location::count())->toBe(8)
        ->and(Location::where('type', 'mine')->count())->toBe(6)
        ->and(Driver::count())->toBe(10)
        ->and(Driver::where('is_active', false)->count())->toBe(1)
        ->and(Vehicle::count())->toBe(16)
        ->and(Vehicle::where('ownership', VehicleOwnership::Rented)->count())->toBe(5)
        ->and(Vehicle::where('status', VehicleStatus::Maintenance)->count())->toBe(2)
        ->and(Vehicle::where('next_service_at', '<=', today()->addDays(14))->count())->toBe(3);
});

test('seeds bookings around now without schedule conflicts', function () {
    $this->seed(DatabaseSeeder::class);

    $bookings = Booking::with('approvals')->get();

    expect($bookings->count())->toBeGreaterThanOrEqual(75)
        ->and($bookings->min('start_at')->greaterThanOrEqualTo(now()->subMonths(6)->startOfDay()))->toBeTrue()
        ->and($bookings->max('start_at')->lessThanOrEqualTo(now()->addDays(15)))->toBeTrue();

    $upcoming = $bookings->filter(fn (Booking $booking) => $booking->start_at->isFuture());
    expect($upcoming->pluck('status')->unique()->map->value->sort()->values()->all())->toBe(['approved', 'pending'])
        ->and($upcoming->where('status', BookingStatus::Pending)->pluck('current_level')->unique()->sort()->values()->all())->toBe([1, 2]);

    $past = $bookings->reject(fn (Booking $booking) => $booking->start_at->isFuture());
    expect($past->pluck('status')->unique()->map->value->sort()->values()->all())->toBe(['cancelled', 'completed', 'rejected']);

    foreach (['vehicle_id', 'driver_id'] as $resource) {
        $bookings->whereIn('status', BookingStatus::active())
            ->groupBy($resource)
            ->each(function ($schedule) {
                $schedule->each(function (Booking $booking) use ($schedule) {
                    $conflicts = $schedule->filter(fn (Booking $other) => $other->isNot($booking)
                        && $other->start_at < $booking->end_at
                        && $other->end_at > $booking->start_at);

                    expect($conflicts)->toBeEmpty();
                });
            });
    }
});

test('seeds approval statuses that match the booking status', function () {
    $this->seed(DatabaseSeeder::class);

    Booking::with('approvals')->get()->each(function (Booking $booking) {
        $statuses = $booking->approvals->mapWithKeys(fn ($approval) => [$approval->level => $approval->status]);

        expect($statuses->count())->toBeIn([2, 3]);

        match ($booking->status) {
            BookingStatus::Approved, BookingStatus::Completed => expect($statuses->unique()->all())->toBe([1 => ApprovalStatus::Approved]),
            BookingStatus::Pending => $statuses->each(fn ($status, $level) => expect($status)->toBe(
                $level < $booking->current_level ? ApprovalStatus::Approved : ApprovalStatus::Pending,
            )),
            BookingStatus::Rejected => expect($statuses[$booking->current_level])->toBe(ApprovalStatus::Rejected)
                ->and($statuses->filter(fn ($status) => $status === ApprovalStatus::Pending))->toBeEmpty(),
            BookingStatus::Cancelled => expect($statuses->filter(fn ($status) => $status === ApprovalStatus::Pending))->toBeEmpty(),
        };
    });
});

test('seeds completed bookings with distance and fuel usage', function () {
    $this->seed(DatabaseSeeder::class);

    Booking::where('status', BookingStatus::Completed)->get()->each(function (Booking $booking) {
        expect($booking->odometer_end - $booking->odometer_start)->toBeGreaterThanOrEqual(50)->toBeLessThanOrEqual(400)
            ->and((float) $booking->fuel_used)->toBeGreaterThan(0)
            ->and($booking->completed_at->greaterThan($booking->end_at))->toBeTrue();
    });
});

test('seeds activity logs for created bookings and approval decisions', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ActivityLog::where('action', 'booking.created')->count())->toBe(Booking::count())
        ->and(ActivityLog::where('action', 'approval.approved')->count())
        ->toBe(BookingApproval::where('status', ApprovalStatus::Approved)->count())
        ->and(ActivityLog::where('action', 'approval.rejected')->count())
        ->toBe(Booking::where('status', BookingStatus::Rejected)->count());
});
