<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\VehicleType;
use App\Http\Requests\ReportRequest;
use App\Http\Resources\BookingResource;
use App\Models\Region;
use App\Models\User;
use App\Services\BookingReportQuery;
use App\Services\ReportService;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(ReportRequest $request): Response
    {
        $report = new BookingReportQuery($request->filters());

        return Inertia::render('reports/index', [
            'filters' => $report->filters,
            'summary' => $report->summary(),
            'bookings' => BookingResource::collection($report->detailed()->paginate(15)->withQueryString()),
            'statuses' => BookingStatus::options(),
            'vehicleTypes' => VehicleType::options(),
            'regions' => Region::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(ReportRequest $request, #[CurrentUser] User $admin): BinaryFileResponse
    {
        return $this->reports->download($admin, new BookingReportQuery($request->filters()));
    }
}
