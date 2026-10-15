<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;

/**
 * Satu sumber filter laporan pemesanan, dipakai halaman laporan dan export.
 *
 * @phpstan-type ReportFilters array{date_from: string, date_to: string, status: string|null, vehicle_type: string|null, region_id: int|null}
 * @phpstan-type ReportSummary array{total: int, approved_or_completed: int, rejected: int, cancelled: int, by_status: array<string, int>, distance: int, fuel: float}
 */
class BookingReportQuery
{
    /**
     * @param  ReportFilters  $filters
     */
    public function __construct(public readonly array $filters) {}

    /**
     * Pemesanan yang masuk laporan; rentang tanggal berdasarkan `start_at`.
     *
     * @return Builder<Booking>
     */
    public function query(): Builder
    {
        return Booking::query()->filter($this->filters);
    }

    /**
     * Pemesanan lengkap dengan relasinya, urut jadwal mulai.
     *
     * @return Builder<Booking>
     */
    public function detailed(): Builder
    {
        return $this->query()
            ->with(['vehicle.location.region', 'driver', 'approvals.approver'])
            ->withCount(Booking::approvalProgressCounts())
            ->orderBy('bookings.start_at')
            ->orderBy('bookings.id');
    }

    /**
     * @return ReportSummary
     */
    public function summary(): array
    {
        $byStatus = $this->query()
            ->toBase()
            ->selectRaw('bookings.status as status, count(*) as total')
            ->groupBy('bookings.status')
            ->pluck('total', 'status')
            ->map(fn (mixed $total) => (int) $total);

        $usage = $this->query()
            ->where('bookings.status', BookingStatus::Completed)
            ->toBase()
            ->selectRaw('coalesce(sum(bookings.odometer_end - bookings.odometer_start), 0) as distance')
            ->selectRaw('coalesce(sum(bookings.fuel_used), 0) as fuel')
            ->first();

        $count = fn (BookingStatus $status): int => $byStatus->get($status->value, 0);

        return [
            'total' => (int) $byStatus->sum(),
            'approved_or_completed' => $count(BookingStatus::Approved) + $count(BookingStatus::Completed),
            'rejected' => $count(BookingStatus::Rejected),
            'cancelled' => $count(BookingStatus::Cancelled),
            'by_status' => array_combine(
                array_map(fn (BookingStatus $status) => $status->value, BookingStatus::cases()),
                array_map($count, BookingStatus::cases()),
            ),
            'distance' => (int) ($usage->distance ?? 0),
            'fuel' => round((float) ($usage->fuel ?? 0), 2),
        ];
    }

    /**
     * Kendaraan yang paling sering dipakai (approved + completed) dalam laporan.
     *
     * @return list<array{plate_number: string, name: string, bookings: int, distance: int}>
     */
    public function topVehicles(int $limit = 5): array
    {
        return array_values($this->query()
            ->whereIn('bookings.status', [BookingStatus::Approved, BookingStatus::Completed])
            ->join('vehicles', 'vehicles.id', '=', 'bookings.vehicle_id')
            ->toBase()
            ->select('vehicles.plate_number', 'vehicles.name')
            ->selectRaw('count(*) as bookings_count')
            ->selectRaw(
                'coalesce(sum(case when bookings.status = ? then bookings.odometer_end - bookings.odometer_start else 0 end), 0) as distance',
                [BookingStatus::Completed->value],
            )
            ->groupBy('vehicles.id', 'vehicles.plate_number', 'vehicles.name')
            ->orderByDesc('bookings_count')
            ->orderByDesc('distance')
            ->orderBy('vehicles.plate_number')
            ->limit($limit)
            ->get()
            ->map(fn (object $row) => [
                'plate_number' => (string) $row->plate_number,
                'name' => (string) $row->name,
                'bookings' => (int) $row->bookings_count,
                'distance' => (int) $row->distance,
            ])
            ->all());
    }
}
