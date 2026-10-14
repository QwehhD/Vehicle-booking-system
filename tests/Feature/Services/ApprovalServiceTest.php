<?php

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Auth\Access\AuthorizationException;

test('re-checks the turn after locking the booking', function (string $method, array $arguments) {
    [$first, $second] = User::factory()->approver()->count(2)->create();
    $booking = Booking::factory()->withApprovers($first, $second)->create();
    $approval = $booking->approvals()->where('level', 1)->sole();

    // Admin sempat membatalkan booking setelah policy di route lolos.
    $booking->update(['status' => BookingStatus::Cancelled]);
    BookingApproval::where('booking_id', $booking->id)->update(['status' => ApprovalStatus::Skipped]);

    expect(fn () => app(ApprovalService::class)->{$method}($first, $approval, ...$arguments))
        ->toThrow(AuthorizationException::class);

    expect($approval->refresh()->status)->toBe(ApprovalStatus::Skipped)
        ->and($booking->refresh()->status)->toBe(BookingStatus::Cancelled);
})->with([
    'approve' => ['approve', [null]],
    'reject' => ['reject', ['Tidak sesuai.']],
]);
