<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalTab;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApprovalService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * Approval milik approver pada tab yang dipilih, beserta ringkasan booking.
     *
     * @return LengthAwarePaginator<int, BookingApproval>
     */
    public function inbox(User $approver, ApprovalTab $tab): LengthAwarePaginator
    {
        $query = $approver->approvals()
            ->with(['booking' => fn (BelongsTo $booking) => $booking
                ->with(['vehicle', 'driver'])
                ->withCount(Booking::approvalProgressCounts())]);

        $query = match ($tab) {
            ApprovalTab::Waiting => $query->awaitingTurn()->orderByBookingStart(),
            ApprovalTab::Queue => $query->awaitingPreviousLevels()->orderByBookingStart(),
            ApprovalTab::History => $query->finished()->latest('updated_at')->latest('id'),
        };

        return $query->paginate(10)->withQueryString();
    }

    /**
     * Jumlah approval di setiap tab inbox.
     *
     * @return array<string, int>
     */
    public function counts(User $approver): array
    {
        return [
            ApprovalTab::Waiting->value => $approver->approvals()->awaitingTurn()->count(),
            ApprovalTab::Queue->value => $approver->approvals()->awaitingPreviousLevels()->count(),
            ApprovalTab::History->value => $approver->approvals()->finished()->count(),
        ];
    }

    /**
     * Menyetujui approval; level terakhir membuat booking disetujui, selain
     * itu giliran berpindah ke level berikutnya.
     */
    public function approve(User $approver, BookingApproval $approval, ?string $note): BookingApproval
    {
        return DB::transaction(function () use ($approver, $approval, $note): BookingApproval {
            [$approval, $booking] = $this->lockForProcessing($approver, $approval, 'approve');

            $totalLevels = BookingApproval::query()->where('booking_id', $booking->id)->count();

            $approval->update([
                'status' => ApprovalStatus::Approved,
                'note' => $note,
                'acted_at' => now(),
            ]);

            $booking->update($approval->level === $totalLevels
                ? ['status' => BookingStatus::Approved]
                : ['current_level' => $booking->current_level + 1]);

            $this->activityLogger->log(
                'approval.approved',
                $booking,
                "{$approver->name} menyetujui {$booking->code} (level {$approval->level} dari {$totalLevels})",
                ['approval_id' => $approval->id, 'level' => $approval->level, 'total_levels' => $totalLevels, 'note' => $note],
            );

            return $approval;
        });
    }

    /**
     * Menolak approval; booking ikut ditolak dan approval lain yang masih
     * pending dilewati.
     */
    public function reject(User $approver, BookingApproval $approval, string $note): BookingApproval
    {
        return DB::transaction(function () use ($approver, $approval, $note): BookingApproval {
            [$approval, $booking] = $this->lockForProcessing($approver, $approval, 'reject');

            $totalLevels = BookingApproval::query()->where('booking_id', $booking->id)->count();

            $approval->update([
                'status' => ApprovalStatus::Rejected,
                'note' => $note,
                'acted_at' => now(),
            ]);

            $booking->update(['status' => BookingStatus::Rejected]);

            BookingApproval::query()
                ->where('booking_id', $booking->id)
                ->where('status', ApprovalStatus::Pending)
                ->update(['status' => ApprovalStatus::Skipped]);

            $this->activityLogger->log(
                'approval.rejected',
                $booking,
                "{$approver->name} menolak {$booking->code} (level {$approval->level} dari {$totalLevels})",
                ['approval_id' => $approval->id, 'level' => $approval->level, 'total_levels' => $totalLevels, 'note' => $note],
            );

            return $approval;
        });
    }

    /**
     * Mengunci booking, memuat ulang approval, lalu memeriksa ulang policy
     * agar dua persetujuan bersamaan tidak memproses level yang sama.
     *
     * @return array{0: BookingApproval, 1: Booking}
     */
    private function lockForProcessing(User $approver, BookingApproval $approval, string $ability): array
    {
        $booking = Booking::query()->lockForUpdate()->findOrFail($approval->booking_id);
        $approval = BookingApproval::query()->findOrFail($approval->id)->setRelation('booking', $booking);

        Gate::forUser($approver)->authorize($ability, $approval);

        return [$approval, $booking];
    }
}
