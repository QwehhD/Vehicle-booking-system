<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalTab;
use App\Enums\BookingStatus;
use App\Http\Requests\Approvals\ApproveRequest;
use App\Http\Requests\Approvals\IndexApprovalRequest;
use App\Http\Requests\Approvals\RejectRequest;
use App\Http\Resources\BookingApprovalResource;
use App\Models\BookingApproval;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalService $approvals) {}

    public function index(IndexApprovalRequest $request, #[CurrentUser] User $approver): Response
    {
        $tab = $request->tab();

        return Inertia::render('approvals/index', [
            'tab' => $tab->value,
            'tabs' => ApprovalTab::options(),
            'counts' => $this->approvals->counts($approver),
            'approvals' => BookingApprovalResource::collection($this->approvals->inbox($approver, $tab)),
        ]);
    }

    public function approve(ApproveRequest $request, BookingApproval $approval, #[CurrentUser] User $approver): RedirectResponse
    {
        $approval = $this->approvals->approve($approver, $approval, $request->note());
        $booking = $approval->booking;

        return back()->with('success', $booking->status === BookingStatus::Approved
            ? "{$booking->code} disetujui di level terakhir. Pemesanan siap digunakan."
            : "{$booking->code} disetujui. Menunggu persetujuan level {$booking->current_level}.");
    }

    public function reject(RejectRequest $request, BookingApproval $approval, #[CurrentUser] User $approver): RedirectResponse
    {
        $approval = $this->approvals->reject($approver, $approval, $request->note());

        return back()->with('success', "{$approval->booking->code} ditolak.");
    }
}
