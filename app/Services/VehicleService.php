<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Vehicle;

class VehicleService
{
    /**
     * Ringkasan pemakaian kendaraan dari pemesanan yang sudah selesai.
     *
     * @return array{trips: int, distance: int, fuel: float, km_per_liter: float|null}
     */
    public function usageSummary(Vehicle $vehicle): array
    {
        $usage = $vehicle->bookings()
            ->where('status', BookingStatus::Completed)
            ->toBase()
            ->selectRaw('count(*) as trips')
            ->selectRaw('coalesce(sum(odometer_end - odometer_start), 0) as distance')
            ->selectRaw('coalesce(sum(fuel_used), 0) as fuel')
            ->first();

        $distance = (int) ($usage->distance ?? 0);
        $fuel = round((float) ($usage->fuel ?? 0), 2);

        return [
            'trips' => (int) ($usage->trips ?? 0),
            'distance' => $distance,
            'fuel' => $fuel,
            'km_per_liter' => $fuel > 0 ? round($distance / $fuel, 1) : null,
        ];
    }
}
