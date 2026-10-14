<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admin melihat semua pemesanan; approver hanya pemesanan yang
     * menjadikannya approver di salah satu level.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin()
            || $booking->approvals()->where('approver_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function cancel(User $user, Booking $booking): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny();
        }

        return in_array($booking->status, BookingStatus::active(), true)
            ? Response::allow()
            : Response::deny('Hanya pemesanan yang menunggu persetujuan atau sudah disetujui yang dapat dibatalkan.');
    }

    public function complete(User $user, Booking $booking): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny();
        }

        return $booking->status === BookingStatus::Approved
            ? Response::allow()
            : Response::deny('Hanya pemesanan yang sudah disetujui yang dapat diselesaikan.');
    }
}
