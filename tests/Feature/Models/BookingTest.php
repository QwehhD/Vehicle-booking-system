<?php

use App\Enums\ApprovalStatus;
use App\Models\Booking;
use App\Models\User;
use Carbon\CarbonImmutable;

test('booking codes use the creation month and the zero padded id', function () {
    $booking = Booking::factory()->create(['created_at' => '2026-10-14 09:00:00']);

    expect($booking->code)->toBe(sprintf('BK-202610-%05d', $booking->id))
        ->and($booking->hasTemporaryCode())->toBeFalse();
});

test('approvals are ordered by level', function () {
    [$first, $second, $third] = User::factory()->approver()->count(3)->create();

    $booking = Booking::factory()->withApprovers($first, $second, $third)->create();

    expect($booking->approvals->pluck('approver_id')->all())
        ->toBe([$first->id, $second->id, $third->id]);
});

test('the current approval is the pending approval at the current level', function () {
    [$first, $second] = User::factory()->approver()->count(2)->create();

    $booking = Booking::factory()
        ->state(['current_level' => 2])
        ->withApprovers($first, $second)
        ->create();

    $current = $booking->load('currentApproval')->currentApproval;

    expect($current->approver_id)->toBe($second->id)
        ->and($current->level)->toBe(2)
        ->and($current->status)->toBe(ApprovalStatus::Pending);
});

test('decided bookings have no current approval', function () {
    [$first, $second] = User::factory()->approver()->count(2)->create();

    $booking = Booking::factory()
        ->approved()
        ->state(['current_level' => 2])
        ->withApprovers($first, $second)
        ->create();

    expect($booking->load('currentApproval')->currentApproval)->toBeNull();
});

test('the overlapping scope ignores schedules that only touch', function () {
    $booking = Booking::factory()->create([
        'start_at' => '2026-10-20 08:00:00',
        'end_at' => '2026-10-20 12:00:00',
    ]);

    $overlapping = fn (string $start, string $end) => Booking::query()
        ->overlapping(new CarbonImmutable($start), new CarbonImmutable($end))
        ->pluck('id')
        ->all();

    expect($overlapping('2026-10-20 11:00:00', '2026-10-20 14:00:00'))->toBe([$booking->id])
        ->and($overlapping('2026-10-20 06:00:00', '2026-10-20 08:30:00'))->toBe([$booking->id])
        ->and($overlapping('2026-10-20 12:00:00', '2026-10-20 15:00:00'))->toBe([])
        ->and($overlapping('2026-10-20 05:00:00', '2026-10-20 08:00:00'))->toBe([]);
});

test('the active scope only includes pending and approved bookings', function () {
    $pending = Booking::factory()->create();
    $approved = Booking::factory()->approved()->create();
    Booking::factory()->rejected()->create();
    Booking::factory()->cancelled()->create();
    Booking::factory()->completed()->create();

    expect(Booking::active()->pluck('id')->sort()->values()->all())
        ->toBe([$pending->id, $approved->id]);
});
