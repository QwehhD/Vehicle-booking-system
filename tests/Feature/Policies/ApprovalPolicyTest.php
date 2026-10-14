<?php

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

test('approvers can process their own approval at their turn', function (string $ability) {
    [$first, $second] = User::factory()->approver()->count(2)->create();
    $booking = Booking::factory()->withApprovers($first, $second)->create();

    expect($first->can($ability, $booking->approvals()->where('level', 1)->sole()))->toBeTrue();
})->with(['approve', 'reject']);

test('approvals can not be processed outside the approver turn', function (Closure $scenario) {
    [$user, $approval] = $scenario();

    expect($user->can('approve', $approval))->toBeFalse()
        ->and($user->can('reject', $approval))->toBeFalse();
})->with([
    'another approver' => function () {
        $booking = Booking::factory()->withApprovers(...User::factory()->approver()->count(2)->create())->create();

        return [User::factory()->approver()->create(), $booking->approvals()->where('level', 1)->sole()];
    },
    'an admin' => function () {
        $booking = Booking::factory()->withApprovers(...User::factory()->approver()->count(2)->create())->create();

        return [User::factory()->admin()->create(), $booking->approvals()->where('level', 1)->sole()];
    },
    'a later level' => function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->withApprovers($first, $second)->create();

        return [$second, $booking->approvals()->where('level', 2)->sole()];
    },
    'an approval already approved' => function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->state(['current_level' => 2])->withApprovers($first, $second)->create();

        return [$first, $booking->approvals()->where('level', 1)->sole()];
    },
]);

test('approvals can not be processed once the booking is decided', function (BookingStatus $status) {
    [$first, $second] = User::factory()->approver()->count(2)->create();
    $booking = Booking::factory()->withApprovers($first, $second)->create();
    $approval = $booking->approvals()->where('level', 1)->sole();

    // Approval sengaja dibiarkan pending agar yang diuji hanya status booking.
    $booking->update(['status' => $status]);

    expect($approval->status)->toBe(ApprovalStatus::Pending)
        ->and($first->can('approve', $approval->refresh()))->toBeFalse();
})->with([BookingStatus::Approved, BookingStatus::Rejected, BookingStatus::Cancelled, BookingStatus::Completed]);
