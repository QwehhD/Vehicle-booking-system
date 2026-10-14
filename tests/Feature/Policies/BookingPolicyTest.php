<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

test('only admins can list and create bookings', function (string $role, bool $allowed) {
    $user = User::factory()->{$role}()->create();

    expect($user->can('viewAny', Booking::class))->toBe($allowed)
        ->and($user->can('create', Booking::class))->toBe($allowed);
})->with([
    'admin' => ['admin', true],
    'approver' => ['approver', false],
]);

test('admins can view any booking', function () {
    expect(User::factory()->admin()->create()->can('view', Booking::factory()->create()))->toBeTrue();
});

test('approvers can view only bookings they approve', function () {
    [$involved, $outsider] = User::factory()->approver()->count(2)->create();
    $booking = Booking::factory()->withApprovers(User::factory()->approver()->create(), $involved)->create();

    expect($involved->can('view', $booking))->toBeTrue()
        ->and($outsider->can('view', $booking))->toBeFalse();
});

test('admins can cancel only pending or approved bookings', function (BookingStatus $status, bool $allowed) {
    $admin = User::factory()->admin()->create();
    $booking = Booking::factory()->create(['status' => $status]);

    expect($admin->can('cancel', $booking))->toBe($allowed);
})->with([
    'pending' => [BookingStatus::Pending, true],
    'approved' => [BookingStatus::Approved, true],
    'rejected' => [BookingStatus::Rejected, false],
    'cancelled' => [BookingStatus::Cancelled, false],
    'completed' => [BookingStatus::Completed, false],
]);

test('admins can complete only approved bookings', function (BookingStatus $status, bool $allowed) {
    $admin = User::factory()->admin()->create();
    $booking = Booking::factory()->create(['status' => $status]);

    expect($admin->can('complete', $booking))->toBe($allowed);
})->with([
    'pending' => [BookingStatus::Pending, false],
    'approved' => [BookingStatus::Approved, true],
    'rejected' => [BookingStatus::Rejected, false],
    'cancelled' => [BookingStatus::Cancelled, false],
    'completed' => [BookingStatus::Completed, false],
]);

test('approvers can not cancel or complete bookings they approve', function () {
    $approver = User::factory()->approver()->create();
    $booking = Booking::factory()->approved()->withApprovers($approver, User::factory()->approver()->create())->create();

    expect($approver->can('cancel', $booking))->toBeFalse()
        ->and($approver->can('complete', $booking))->toBeFalse();
});
