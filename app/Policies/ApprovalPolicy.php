<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Models\BookingApproval;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ApprovalPolicy
{
    public function approve(User $user, BookingApproval $approval): Response
    {
        return $this->process($user, $approval);
    }

    public function reject(User $user, BookingApproval $approval): Response
    {
        return $this->process($user, $approval);
    }

    /**
     * Approval hanya dapat diproses oleh pemiliknya, selama approval dan
     * booking masih pending dan levelnya sedang menjadi giliran.
     */
    private function process(User $user, BookingApproval $approval): Response
    {
        if (! $user->isApprover() || $approval->approver_id !== $user->id) {
            return Response::deny('Persetujuan ini bukan milik Anda.');
        }

        $booking = $approval->booking;

        if ($approval->status !== ApprovalStatus::Pending || $booking->status !== BookingStatus::Pending) {
            return Response::deny('Persetujuan ini sudah diproses.');
        }

        if ($approval->level !== $booking->current_level) {
            return Response::deny("Belum giliran Anda. Persetujuan masih menunggu level {$booking->current_level}.");
        }

        return Response::allow();
    }
}
