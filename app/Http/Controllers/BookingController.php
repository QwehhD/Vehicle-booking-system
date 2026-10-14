<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\VehicleType;
use App\Http\Requests\Bookings\CancelBookingRequest;
use App\Http\Requests\Bookings\CompleteBookingRequest;
use App\Http\Requests\Bookings\IndexBookingRequest;
use App\Http\Requests\Bookings\StoreBookingRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Region;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(IndexBookingRequest $request): Response
    {
        $filters = $request->filters();

        $bookings = Booking::query()
            ->with(['vehicle', 'driver'])
            ->withCount(Booking::approvalProgressCounts())
            ->filter($filters)
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('bookings/index', [
            'bookings' => BookingResource::collection($bookings),
            'filters' => $filters,
            'statuses' => BookingStatus::options(),
            'vehicleTypes' => VehicleType::options(),
            'regions' => Region::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('bookings/create', $this->bookings->formOptions());
    }

    public function store(StoreBookingRequest $request, #[CurrentUser] User $admin): RedirectResponse
    {
        $booking = $this->bookings->create($admin, $request->bookingData());

        return to_route('bookings.show', $booking)
            ->with('success', "Pemesanan {$booking->code} berhasil dibuat.");
    }

    public function show(Booking $booking, #[CurrentUser] User $user): Response
    {
        $booking
            ->load(['vehicle.location.region', 'driver.location', 'creator', 'approvals.approver', 'activityLogs.user'])
            ->loadCount(Booking::approvalProgressCounts());

        return Inertia::render('bookings/show', [
            'booking' => BookingResource::make($booking),
            'activityLogs' => ActivityLogResource::collection($booking->activityLogs),
            'can' => [
                'cancel' => $user->can('cancel', $booking),
                'complete' => $user->can('complete', $booking),
            ],
            'actionableApproval' => $this->actionableApproval($booking, $user),
        ]);
    }

    public function cancel(CancelBookingRequest $request, Booking $booking, #[CurrentUser] User $admin): RedirectResponse
    {
        $booking = $this->bookings->cancel($admin, $booking, $request->string('cancel_reason')->toString());

        return back()->with('success', "Pemesanan {$booking->code} dibatalkan.");
    }

    public function complete(CompleteBookingRequest $request, Booking $booking, #[CurrentUser] User $admin): RedirectResponse
    {
        $booking = $this->bookings->complete($admin, $booking, $request->completionData());

        return back()->with('success', "Pemesanan {$booking->code} diselesaikan.");
    }

    /**
     * Approval milik user yang sedang menjadi gilirannya pada booking ini,
     * untuk menampilkan tombol Setujui/Tolak. Policy diperiksa pada salinan
     * approval agar relasi booking tidak ikut terserialisasi ke props.
     *
     * @return array{id: int, level: int, total_levels: int}|null
     */
    private function actionableApproval(Booking $booking, User $user): ?array
    {
        $approval = $booking->approvals->firstWhere('approver_id', $user->id);

        if ($approval === null || ! $user->can('approve', (clone $approval)->setRelation('booking', $booking))) {
            return null;
        }

        return [
            'id' => $approval->id,
            'level' => $approval->level,
            'total_levels' => $booking->approvals->count(),
        ];
    }
}
