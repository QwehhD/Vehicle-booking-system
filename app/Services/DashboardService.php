<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\VehicleOwnership;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Http\Resources\BookingApprovalResource;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Query agregasi dashboard. Metrik pemesanan disaring berdasarkan tahun
 * `start_at` dan region pool kendaraan; metrik kendaraan hanya oleh region.
 *
 * @phpstan-type Filters array{year: int, region_id: int|null}
 * @phpstan-type MonthlyBookings list<array{month: int, label: string, completed: int, approved: int, pending: int, cancelled: int, rejected: int}>
 * @phpstan-type TopVehicles list<array{id: int, plate_number: string, name: string, bookings: int, distance: int}>
 */
class DashboardService
{
    private const MONTH_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];

    /**
     * @param  Filters  $filters
     * @return array<string, mixed>
     */
    public function admin(array $filters): array
    {
        return [
            'stats' => $this->adminStats($filters),
            'bookingsPerMonth' => $this->bookingsPerMonth($filters),
            'topVehicles' => $this->topVehicles($filters),
            'composition' => $this->usageComposition($filters),
            'distanceAndFuel' => $this->distanceAndFuelPerMonth($filters),
            'serviceDue' => $this->vehiclesDueForService($filters['region_id']),
        ];
    }

    /**
     * @param  Filters  $filters
     * @return array<string, mixed>
     */
    public function approver(User $approver, array $filters): array
    {
        return [
            'stats' => $this->approverStats($approver, $filters),
            'waitingApprovals' => $this->latestWaitingApprovals($approver, $filters),
            'bookingsPerMonth' => $this->bookingsPerMonth($filters),
            'topVehicles' => $this->topVehicles($filters),
        ];
    }

    /**
     * Tahun yang dapat dipilih: dari pemesanan paling awal sampai tahun berjalan
     * atau tahun pemesanan paling akhir, terbaru lebih dulu.
     *
     * @return list<int>
     */
    public function availableYears(): array
    {
        $first = Booking::query()->min('start_at');
        $last = Booking::query()->max('start_at');
        $currentYear = now()->year;

        $from = $first ? min(CarbonImmutable::parse($first)->year, $currentYear) : $currentYear;
        $to = $last ? max(CarbonImmutable::parse($last)->year, $currentYear) : $currentYear;

        return range($to, $from);
    }

    /**
     * @param  Filters  $filters
     * @return array{total: int, pending: int, approved: int, completed_this_month: int, vehicles_available: int, vehicles_total: int, vehicles_service_due: int}
     */
    private function adminStats(array $filters): array
    {
        $byStatus = $this->countByStatus($this->bookings($filters));

        $completedThisMonth = $this->inRegion(Booking::query(), $filters['region_id'])
            ->where('bookings.status', BookingStatus::Completed)
            ->whereBetween('bookings.completed_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $vehicles = fn () => $this->vehiclesInRegion($filters['region_id']);

        return [
            'total' => array_sum($byStatus),
            'pending' => $byStatus[BookingStatus::Pending->value] ?? 0,
            'approved' => $byStatus[BookingStatus::Approved->value] ?? 0,
            'completed_this_month' => $completedThisMonth,
            'vehicles_available' => $vehicles()->where('status', VehicleStatus::Available)->count(),
            'vehicles_total' => $vehicles()->count(),
            'vehicles_service_due' => $vehicles()->where('next_service_at', '<=', $this->serviceDueLimit())->count(),
        ];
    }

    /**
     * @param  Filters  $filters
     * @return array{waiting: int, approved: int, rejected: int}
     */
    private function approverStats(User $approver, array $filters): array
    {
        $approvals = fn () => $this->approvalsOfBookings($approver, $filters);

        $decided = $approvals()
            ->whereIn('status', [ApprovalStatus::Approved, ApprovalStatus::Rejected])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->toBase()
            ->pluck('total', 'status');

        return [
            'waiting' => $approvals()->awaitingTurn()->count(),
            'approved' => (int) ($decided[ApprovalStatus::Approved->value] ?? 0),
            'rejected' => (int) ($decided[ApprovalStatus::Rejected->value] ?? 0),
        ];
    }

    /**
     * Lima approval terbaru yang sedang menjadi giliran approver.
     *
     * @param  Filters  $filters
     */
    private function latestWaitingApprovals(User $approver, array $filters): AnonymousResourceCollection
    {
        $approvals = $this->approvalsOfBookings($approver, $filters)
            ->awaitingTurn()
            ->with(['booking' => fn (BelongsTo $booking) => $booking
                ->with(['vehicle', 'driver'])
                ->withCount(Booking::approvalProgressCounts())])
            ->orderByDesc(Booking::query()->select('created_at')->whereColumn('bookings.id', 'booking_approvals.booking_id'))
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return BookingApprovalResource::collection($approvals);
    }

    /**
     * Jumlah pemesanan per bulan, dipecah per status, untuk 12 bulan.
     *
     * @param  Filters  $filters
     * @return MonthlyBookings
     */
    private function bookingsPerMonth(array $filters): array
    {
        $month = $this->monthOf('bookings.start_at');

        $rows = $this->bookings($filters)
            ->selectRaw("{$month} as month, bookings.status as status, count(*) as total")
            ->groupByRaw($month)
            ->groupBy('bookings.status')
            ->toBase()
            ->get();

        return array_map(function (int $month) use ($rows): array {
            $counts = [];

            foreach (BookingStatus::cases() as $status) {
                $counts[$status->value] = (int) $rows
                    ->where('month', $month)
                    ->where('status', $status->value)
                    ->sum('total');
            }

            return [
                'month' => $month,
                'label' => self::MONTH_LABELS[$month - 1],
                'completed' => $counts[BookingStatus::Completed->value],
                'approved' => $counts[BookingStatus::Approved->value],
                'pending' => $counts[BookingStatus::Pending->value],
                'cancelled' => $counts[BookingStatus::Cancelled->value],
                'rejected' => $counts[BookingStatus::Rejected->value],
            ];
        }, range(1, 12));
    }

    /**
     * Sepuluh kendaraan dengan pemakaian terbanyak (approved + completed),
     * beserta total km dari pemakaian yang sudah selesai.
     *
     * @param  Filters  $filters
     * @return TopVehicles
     */
    private function topVehicles(array $filters): array
    {
        return array_values($this->usage($filters)
            ->join('vehicles', 'vehicles.id', '=', 'bookings.vehicle_id')
            ->select('vehicles.id', 'vehicles.plate_number', 'vehicles.name')
            ->selectRaw('count(*) as bookings_count')
            ->selectRaw($this->distanceSum().' as distance', [BookingStatus::Completed->value])
            ->groupBy('vehicles.id', 'vehicles.plate_number', 'vehicles.name')
            ->orderByDesc('bookings_count')
            ->orderByDesc('distance')
            ->orderBy('vehicles.plate_number')
            ->limit(10)
            ->toBase()
            ->get()
            ->map(fn (object $row) => [
                'id' => (int) $row->id,
                'plate_number' => (string) $row->plate_number,
                'name' => (string) $row->name,
                'bookings' => (int) $row->bookings_count,
                'distance' => (int) $row->distance,
            ])
            ->all());
    }

    /**
     * Komposisi pemakaian berdasarkan jenis dan kepemilikan kendaraan.
     *
     * @param  Filters  $filters
     * @return array{type: list<array{key: string, label: string, value: int}>, ownership: list<array{key: string, label: string, value: int}>}
     */
    private function usageComposition(array $filters): array
    {
        $types = $this->usageCountBy($filters, 'type');
        $ownerships = $this->usageCountBy($filters, 'ownership');

        return [
            'type' => array_map(fn (VehicleType $type) => [
                'key' => $type->value,
                'label' => $type->label(),
                'value' => (int) ($types[$type->value] ?? 0),
            ], VehicleType::cases()),
            'ownership' => array_map(fn (VehicleOwnership $ownership) => [
                'key' => $ownership->value,
                'label' => $ownership->label(),
                'value' => (int) ($ownerships[$ownership->value] ?? 0),
            ], VehicleOwnership::cases()),
        ];
    }

    /**
     * Jumlah pemakaian per nilai kolom kendaraan.
     *
     * @param  Filters  $filters
     * @param  literal-string  $column
     * @return Collection<string, int>
     */
    private function usageCountBy(array $filters, string $column): Collection
    {
        return $this->usage($filters)
            ->join('vehicles', 'vehicles.id', '=', 'bookings.vehicle_id')
            ->selectRaw("vehicles.{$column} as value, count(*) as total")
            ->groupBy("vehicles.{$column}")
            ->toBase()
            ->pluck('total', 'value')
            ->map(fn (mixed $total) => (int) $total);
    }

    /**
     * Total km dan BBM per bulan dari pemesanan yang sudah selesai.
     *
     * @param  Filters  $filters
     * @return list<array{month: int, label: string, distance: int, fuel: float}>
     */
    private function distanceAndFuelPerMonth(array $filters): array
    {
        $month = $this->monthOf('bookings.start_at');

        $rows = $this->bookings($filters)
            ->where('bookings.status', BookingStatus::Completed)
            ->selectRaw("{$month} as month")
            ->selectRaw('sum(bookings.odometer_end - bookings.odometer_start) as distance')
            ->selectRaw('sum(bookings.fuel_used) as fuel')
            ->groupByRaw($month)
            ->toBase()
            ->get()
            ->keyBy(fn (object $row) => (int) $row->month);

        return array_map(fn (int $month) => [
            'month' => $month,
            'label' => self::MONTH_LABELS[$month - 1],
            'distance' => (int) ($rows->get($month)->distance ?? 0),
            'fuel' => round((float) ($rows->get($month)->fuel ?? 0), 2),
        ], range(1, 12));
    }

    /**
     * Kendaraan dengan jadwal service paling lambat 14 hari lagi (termasuk
     * yang sudah lewat), yang paling mendesak lebih dulu.
     *
     * @return list<array{id: int, plate_number: string, name: string, location: string, status_label: string, status_color: string, next_service_at: string, days_left: int}>
     */
    private function vehiclesDueForService(?int $regionId): array
    {
        $today = today();

        return array_values($this->vehiclesInRegion($regionId)
            ->with('location')
            ->where('next_service_at', '<=', $this->serviceDueLimit())
            ->orderBy('next_service_at')
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'name' => $vehicle->name,
                'location' => $vehicle->location->name,
                'status_label' => $vehicle->status->label(),
                'status_color' => $vehicle->status->color(),
                'next_service_at' => (string) $vehicle->next_service_at?->toDateString(),
                'days_left' => (int) $today->diffInDays($vehicle->next_service_at, false),
            ])
            ->all());
    }

    /**
     * Pemesanan pada tahun dan region yang dipilih.
     *
     * @param  Filters  $filters
     * @return Builder<Booking>
     */
    private function bookings(array $filters): Builder
    {
        $start = CarbonImmutable::create($filters['year'])->startOfYear();

        return $this->inRegion(Booking::query(), $filters['region_id'])
            ->where('bookings.start_at', '>=', $start)
            ->where('bookings.start_at', '<', $start->addYear());
    }

    /**
     * Pemesanan yang benar-benar memakai kendaraan (approved + completed).
     *
     * @param  Filters  $filters
     * @return Builder<Booking>
     */
    private function usage(array $filters): Builder
    {
        return $this->bookings($filters)
            ->whereIn('bookings.status', [BookingStatus::Approved, BookingStatus::Completed]);
    }

    /**
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    private function inRegion(Builder $query, ?int $regionId): Builder
    {
        return $query->when($regionId, fn (Builder $query, int $regionId) => $query
            ->whereHas('vehicle.location', fn (Builder $location) => $location->where('region_id', $regionId)));
    }

    /**
     * @return Builder<Vehicle>
     */
    private function vehiclesInRegion(?int $regionId): Builder
    {
        return Vehicle::query()->when($regionId, fn (Builder $query, int $regionId) => $query
            ->whereHas('location', fn (Builder $location) => $location->where('region_id', $regionId)));
    }

    /**
     * Approval milik approver untuk pemesanan pada tahun dan region yang dipilih.
     *
     * @param  Filters  $filters
     * @return Builder<BookingApproval>
     */
    private function approvalsOfBookings(User $approver, array $filters): Builder
    {
        $bookingIds = $this->bookings($filters)->select('bookings.id');

        return BookingApproval::query()
            ->where('approver_id', $approver->id)
            ->whereIn('booking_id', $bookingIds);
    }

    /**
     * @param  Builder<Booking>  $query
     * @return array<string, int>
     */
    private function countByStatus(Builder $query): array
    {
        return $query
            ->selectRaw('bookings.status as status, count(*) as total')
            ->groupBy('bookings.status')
            ->toBase()
            ->pluck('total', 'status')
            ->map(fn (mixed $total) => (int) $total)
            ->all();
    }

    /**
     * Total km pemesanan selesai; binding pertama adalah status `completed`.
     *
     * @return literal-string
     */
    private function distanceSum(): string
    {
        return 'coalesce(sum(case when bookings.status = ? then bookings.odometer_end - bookings.odometer_start else 0 end), 0)';
    }

    private function serviceDueLimit(): CarbonImmutable
    {
        return today()->addDays(14);
    }

    /**
     * Ekspresi SQL nomor bulan (1–12) yang sesuai dengan driver database.
     *
     * @param  literal-string  $column
     * @return literal-string
     */
    private function monthOf(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "cast(strftime('%m', {$column}) as integer)",
            'pgsql' => "cast(extract(month from {$column}) as integer)",
            default => "month({$column})",
        };
    }
}
