<?php

use App\Models\Booking;
use App\Models\Location;
use App\Models\Region;
use App\Models\Vehicle;
use App\Services\BookingReportQuery;

/**
 * @param  array<string, mixed>  $filters
 */
function reportQuery(array $filters = []): BookingReportQuery
{
    return new BookingReportQuery([
        'date_from' => '2026-10-01',
        'date_to' => '2026-10-31',
        'status' => null,
        'vehicle_type' => null,
        'region_id' => null,
        ...$filters,
    ]);
}

test('includes bookings that start within the date range, inclusively', function () {
    Booking::factory()->create(['start_at' => '2026-09-30 23:00:00', 'end_at' => '2026-10-01 02:00:00']);
    $first = Booking::factory()->create(['start_at' => '2026-10-01 00:00:00', 'end_at' => '2026-10-01 03:00:00']);
    $last = Booking::factory()->create(['start_at' => '2026-10-31 23:00:00', 'end_at' => '2026-11-01 02:00:00']);
    Booking::factory()->create(['start_at' => '2026-11-01 00:00:00', 'end_at' => '2026-11-01 03:00:00']);

    expect(reportQuery()->detailed()->pluck('id')->all())->toBe([$first->id, $last->id]);
});

test('applies the status, vehicle type, and region filters together', function () {
    $region = Region::factory()->create();
    $cargo = Vehicle::factory()->cargo()->for(Location::factory()->for($region))->create();
    $match = Booking::factory()->approved()->for($cargo)->create(['start_at' => '2026-10-20 08:00:00', 'end_at' => '2026-10-20 10:00:00']);
    Booking::factory()->for($cargo)->create(['start_at' => '2026-10-21 08:00:00', 'end_at' => '2026-10-21 10:00:00']);
    Booking::factory()->approved()->for(Vehicle::factory()->cargo())->create(['start_at' => '2026-10-22 08:00:00', 'end_at' => '2026-10-22 10:00:00']);

    $report = reportQuery(['status' => 'approved', 'vehicle_type' => 'cargo', 'region_id' => $region->id]);

    expect($report->query()->pluck('id')->all())->toBe([$match->id]);
});

test('summarizes counts per status with distance and fuel of completed bookings', function () {
    $vehicle = Vehicle::factory()->create();
    $inRange = ['start_at' => '2026-10-10 08:00:00', 'end_at' => '2026-10-10 12:00:00'];
    Booking::factory()->completed()->for($vehicle)->create([...$inRange, 'odometer_start' => 1_000, 'odometer_end' => 1_150, 'fuel_used' => 15.5]);
    Booking::factory()->completed()->for($vehicle)->create([...$inRange, 'start_at' => '2026-10-11 08:00:00', 'end_at' => '2026-10-11 12:00:00', 'odometer_start' => 2_000, 'odometer_end' => 2_050, 'fuel_used' => 4.25]);
    Booking::factory()->approved()->create($inRange);
    Booking::factory()->rejected()->create($inRange);
    Booking::factory()->cancelled()->count(2)->create($inRange);

    expect(reportQuery()->summary())->toBe([
        'total' => 6,
        'approved_or_completed' => 3,
        'rejected' => 1,
        'cancelled' => 2,
        'by_status' => ['pending' => 0, 'approved' => 1, 'rejected' => 1, 'cancelled' => 2, 'completed' => 2],
        'distance' => 200,
        'fuel' => 19.75,
    ]);
});

test('ranks vehicles by usage within the report', function () {
    $busy = Vehicle::factory()->create(['plate_number' => 'DT 1111 AA']);
    $range = ['start_at' => '2026-10-10 08:00:00', 'end_at' => '2026-10-10 12:00:00'];
    Booking::factory()->approved()->for($busy)->create($range);
    Booking::factory()->completed()->for($busy)->create(['start_at' => '2026-10-12 08:00:00', 'end_at' => '2026-10-12 12:00:00', 'odometer_start' => 100, 'odometer_end' => 180]);
    Booking::factory()->rejected()->count(3)->create($range);

    expect(reportQuery()->topVehicles())->toBe([
        ['plate_number' => 'DT 1111 AA', 'name' => $busy->name, 'bookings' => 2, 'distance' => 80],
    ]);
});
