<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Policies\ApprovalPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\BookingApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $booking_id
 * @property int $approver_id
 * @property int $level
 * @property ApprovalStatus $status
 * @property string|null $note
 * @property CarbonImmutable|null $acted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Booking $booking
 * @property-read User $approver
 */
#[Fillable(['booking_id', 'approver_id', 'level', 'status', 'note', 'acted_at'])]
#[UsePolicy(ApprovalPolicy::class)]
class BookingApproval extends Model
{
    /** @use HasFactory<BookingApprovalFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'status' => ApprovalStatus::class,
            'acted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Approval yang sedang menjadi giliran approver-nya: masih pending,
     * booking-nya pending, dan levelnya sama dengan current_level booking.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function awaitingTurn(Builder $query): void
    {
        $query->where('status', ApprovalStatus::Pending)
            ->whereHas('booking', fn (Builder $booking) => $booking
                ->where('status', BookingStatus::Pending)
                ->whereColumn('bookings.current_level', 'booking_approvals.level'));
    }

    /**
     * Approval yang masih menunggu level sebelumnya diproses.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function awaitingPreviousLevels(Builder $query): void
    {
        $query->where('status', ApprovalStatus::Pending)
            ->whereHas('booking', fn (Builder $booking) => $booking
                ->where('status', BookingStatus::Pending)
                ->whereColumn('bookings.current_level', '<', 'booking_approvals.level'));
    }

    /**
     * Approval yang sudah disetujui, ditolak, atau dilewati.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function finished(Builder $query): void
    {
        $query->whereIn('status', [ApprovalStatus::Approved, ApprovalStatus::Rejected, ApprovalStatus::Skipped]);
    }

    /**
     * Urutkan berdasarkan jadwal mulai booking, yang terdekat lebih dulu.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function orderByBookingStart(Builder $query): void
    {
        $query->orderBy(
            Booking::query()->select('start_at')->whereColumn('bookings.id', 'booking_approvals.booking_id'),
        )->orderBy('id');
    }
}
