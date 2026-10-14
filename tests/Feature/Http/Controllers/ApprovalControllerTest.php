<?php

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-14 08:00:00');
});

/**
 * Inbox approver dengan satu booking untuk setiap kondisi tab.
 *
 * @return array<string, mixed>
 */
function approvalInbox(): array
{
    $me = User::factory()->approver()->create();
    $other = User::factory()->approver()->create();

    $laterTurn = Booking::factory()->withApprovers($me, $other)
        ->create(['start_at' => '2026-10-25 08:00:00', 'end_at' => '2026-10-25 12:00:00']);
    $soonerTurn = Booking::factory()->state(['current_level' => 2])->withApprovers($other, $me)
        ->create(['start_at' => '2026-10-21 08:00:00', 'end_at' => '2026-10-21 12:00:00']);
    $queued = Booking::factory()->withApprovers($other, $me)->create();
    $approved = Booking::factory()->approved()->state(['current_level' => 2])->withApprovers($me, $other)->create();
    $skipped = Booking::factory()->rejected()->withApprovers($other, $me)->create();
    Booking::factory()->withApprovers($other, User::factory()->approver()->create())->create();

    return compact('me', 'laterTurn', 'soonerTurn', 'queued', 'approved', 'skipped');
}

describe('index', function () {
    test('lists approvals at my turn, nearest schedule first', function () {
        ['me' => $me, 'laterTurn' => $laterTurn, 'soonerTurn' => $soonerTurn] = approvalInbox();

        $this->actingAs($me)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('approvals/index')
                ->where('tab', 'waiting')
                ->where('counts', ['waiting' => 2, 'queue' => 1, 'history' => 2])
                ->has('approvals.data', 2)
                ->where('approvals.data.0.booking.id', $soonerTurn->id)
                ->where('approvals.data.0.level', 2)
                ->where('approvals.data.0.booking.approval_progress', ['approved' => 1, 'total' => 2])
                ->where('approvals.data.0.can_process', true)
                ->where('approvals.data.1.booking.id', $laterTurn->id));
    });

    test('lists approvals waiting for a previous level in the queue tab', function () {
        ['me' => $me, 'queued' => $queued] = approvalInbox();

        $this->actingAs($me)
            ->get(route('approvals.index', ['tab' => 'queue']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'queue')
                ->has('approvals.data', 1)
                ->where('approvals.data.0.booking.id', $queued->id)
                ->where('approvals.data.0.can_process', false));
    });

    test('lists approved, rejected, and skipped approvals in the history tab', function () {
        ['me' => $me, 'approved' => $approved, 'skipped' => $skipped] = approvalInbox();

        $this->actingAs($me)
            ->get(route('approvals.index', ['tab' => 'history']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'history')
                ->has('approvals.data', 2)
                ->where('approvals.data.0.booking.id', $skipped->id)
                ->where('approvals.data.0.status', 'skipped')
                ->where('approvals.data.1.booking.id', $approved->id)
                ->where('approvals.data.1.status', 'approved'));
    });

    test('redirects an unknown tab to the default tab', function () {
        $this->actingAs(User::factory()->approver()->create())
            ->get(route('approvals.index', ['tab' => 'semua']))
            ->assertRedirect(route('approvals.index'));
    });
});

describe('approve', function () {
    test('moves the turn to the next level after approving level 1', function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->withApprovers($first, $second)->create();
        $approval = $booking->approvals()->where('level', 1)->sole();

        $this->actingAs($first)
            ->from(route('approvals.index'))
            ->post(route('approvals.approve', $approval), ['note' => 'Pastikan driver cukup istirahat.'])
            ->assertRedirect(route('approvals.index'))
            ->assertSessionHas('success', "{$booking->code} disetujui. Menunggu persetujuan level 2.");

        expect($approval->refresh())
            ->status->toBe(ApprovalStatus::Approved)
            ->note->toBe('Pastikan driver cukup istirahat.')
            ->acted_at->toEqual(now())
            ->and($booking->refresh())
            ->status->toBe(BookingStatus::Pending)
            ->current_level->toBe(2);

        $log = ActivityLog::where('action', 'approval.approved')->sole();
        expect($log)
            ->user_id->toBe($first->id)
            ->subject_type->toBe('booking')
            ->subject_id->toBe($booking->id)
            ->description->toBe("{$first->name} menyetujui {$booking->code} (level 1 dari 2)")
            ->properties->toBe([
                'approval_id' => $approval->id,
                'level' => 1,
                'total_levels' => 2,
                'note' => 'Pastikan driver cukup istirahat.',
            ]);
    });

    test('approves the booking when the last level approves', function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->state(['current_level' => 2])->withApprovers($first, $second)->create();
        $approval = $booking->approvals()->where('level', 2)->sole();

        $this->actingAs($second)
            ->post(route('approvals.approve', $approval))
            ->assertSessionHas('success', "{$booking->code} disetujui di level terakhir. Pemesanan siap digunakan.");

        expect($approval->refresh())
            ->status->toBe(ApprovalStatus::Approved)
            ->note->toBeNull()
            ->and($booking->refresh())
            ->status->toBe(BookingStatus::Approved)
            ->current_level->toBe(2);

        expect(ActivityLog::where('action', 'approval.approved')->sole()->description)
            ->toBe("{$second->name} menyetujui {$booking->code} (level 2 dari 2)");
    });

    test('forbids level 2 from approving before level 1', function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->withApprovers($first, $second)->create();
        $approval = $booking->approvals()->where('level', 2)->sole();

        $this->actingAs($second)
            ->post(route('approvals.approve', $approval))
            ->assertForbidden();

        expect($approval->refresh()->status)->toBe(ApprovalStatus::Pending)
            ->and($booking->refresh()->current_level)->toBe(1);
    });

    test('forbids approvers from processing approvals of other approvers', function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->withApprovers($first, $second)->create();
        $approval = $booking->approvals()->where('level', 1)->sole();

        $this->actingAs($second)
            ->post(route('approvals.approve', $approval))
            ->assertForbidden();

        $this->actingAs(User::factory()->approver()->create())
            ->post(route('approvals.reject', $approval), ['note' => 'Bukan urusan saya.'])
            ->assertForbidden();

        expect($approval->refresh()->status)->toBe(ApprovalStatus::Pending);
    });

    test('forbids admins from approving', function () {
        $booking = Booking::factory()->withApprovers(...User::factory()->approver()->count(2)->create())->create();
        $approval = $booking->approvals()->where('level', 1)->sole();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('approvals.approve', $approval))
            ->assertForbidden();

        expect($approval->refresh()->status)->toBe(ApprovalStatus::Pending);
    });

    test('forbids processing an approval of a booking that is no longer pending', function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->cancelled()->withApprovers($first, $second)->create();
        $approval = $booking->approvals()->where('level', 1)->sole();

        $this->actingAs($first)
            ->post(route('approvals.approve', $approval))
            ->assertForbidden();

        expect($approval->refresh()->status)->toBe(ApprovalStatus::Skipped);
    });
});

describe('reject', function () {
    test('requires a note', function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->withApprovers($first, $second)->create();
        $approval = $booking->approvals()->where('level', 1)->sole();

        $this->actingAs($first)
            ->post(route('approvals.reject', $approval), ['note' => '  '])
            ->assertSessionHasErrors(['note' => 'Catatan wajib diisi saat menolak.']);

        expect($approval->refresh()->status)->toBe(ApprovalStatus::Pending)
            ->and($booking->refresh()->status)->toBe(BookingStatus::Pending);
    });

    test('rejects the booking and skips the remaining approvals', function () {
        [$first, $second, $third] = User::factory()->approver()->count(3)->create();
        $booking = Booking::factory()->state(['current_level' => 2])->withApprovers($first, $second, $third)->create();
        $approval = $booking->approvals()->where('level', 2)->sole();

        $this->actingAs($second)
            ->post(route('approvals.reject', $approval), ['note' => 'Mohon lampirkan surat tugas.'])
            ->assertSessionHas('success', "{$booking->code} ditolak.");

        expect($booking->refresh()->status)->toBe(BookingStatus::Rejected)
            ->and($booking->approvals()->get()->map->only('level', 'status', 'note')->all())->toBe([
                ['level' => 1, 'status' => ApprovalStatus::Approved, 'note' => null],
                ['level' => 2, 'status' => ApprovalStatus::Rejected, 'note' => 'Mohon lampirkan surat tugas.'],
                ['level' => 3, 'status' => ApprovalStatus::Skipped, 'note' => null],
            ]);

        $log = ActivityLog::where('action', 'approval.rejected')->sole();
        expect($log)
            ->user_id->toBe($second->id)
            ->description->toBe("{$second->name} menolak {$booking->code} (level 2 dari 3)")
            ->properties->toBe([
                'approval_id' => $approval->id,
                'level' => 2,
                'total_levels' => 3,
                'note' => 'Mohon lampirkan surat tugas.',
            ]);
    });

    test('forbids rejecting before it is my turn', function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->withApprovers($first, $second)->create();
        $approval = BookingApproval::where('booking_id', $booking->id)->where('level', 2)->sole();

        $this->actingAs($second)
            ->post(route('approvals.reject', $approval), ['note' => 'Terlalu cepat.'])
            ->assertForbidden();

        expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
    });
});
