<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\VehicleOwnership;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Http\Requests\VehicleIndexRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\VehicleResource;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function __construct(private VehicleService $vehicles) {}

    public function index(VehicleIndexRequest $request): Response
    {
        $filters = $request->filters();

        $vehicles = Vehicle::query()
            ->with('location.region')
            ->withCount(['bookings as completed_bookings_count' => fn (Builder $query) => $query
                ->where('status', BookingStatus::Completed)])
            ->filter($filters)
            ->orderBy('plate_number')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('vehicles/index', [
            'vehicles' => VehicleResource::collection($vehicles),
            'filters' => $filters,
            'types' => VehicleType::options(),
            'ownerships' => VehicleOwnership::options(),
            'statuses' => VehicleStatus::options(),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Vehicle $vehicle): Response
    {
        $vehicle->load('location.region');

        $bookings = $vehicle->bookings()
            ->with('driver')
            ->withCount(Booking::approvalProgressCounts())
            ->latest('start_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('vehicles/show', [
            'vehicle' => VehicleResource::make($vehicle),
            'summary' => $this->vehicles->usageSummary($vehicle),
            'bookings' => BookingResource::collection($bookings),
        ]);
    }
}
