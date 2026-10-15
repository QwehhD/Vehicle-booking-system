<?php

use App\Models\Booking;
use App\Models\Location;
use App\Models\Region;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

beforeEach(function () {
    $this->travelTo('2026-10-14 08:00:00');
});

/**
 * Booking selesai dengan jarak dan BBM tertentu.
 */
function completedBooking(Vehicle $vehicle, string $startAt, int $distance, float $fuel): Booking
{
    return Booking::factory()->completed()->for($vehicle)->create([
        'start_at' => $startAt,
        'end_at' => CarbonImmutable::parse($startAt)->addHours(4),
        'odometer_start' => 10_000,
        'odometer_end' => 10_000 + $distance,
        'fuel_used' => $fuel,
    ]);
}

test('counts bookings per month and status within the selected year', function () {
    $vehicle = Vehicle::factory()->create();
    Booking::factory()->for($vehicle)->create(['start_at' => '2026-03-02 08:00:00', 'end_at' => '2026-03-02 12:00:00']);
    Booking::factory()->rejected()->for($vehicle)->create(['start_at' => '2026-03-20 08:00:00', 'end_at' => '2026-03-20 12:00:00']);
    completedBooking($vehicle, '2026-11-05 08:00:00', 100, 10);
    Booking::factory()->for($vehicle)->create(['start_at' => '2025-03-02 08:00:00', 'end_at' => '2025-03-02 12:00:00']);

    $months = app(DashboardService::class)->admin(['year' => 2026, 'region_id' => null])['bookingsPerMonth'];

    expect($months)->toHaveCount(12)
        ->and($months[2])->toBe([
            'month' => 3, 'label' => 'Mar', 'completed' => 0, 'approved' => 0, 'pending' => 1, 'cancelled' => 0, 'rejected' => 1,
        ])
        ->and($months[10]['completed'])->toBe(1)
        ->and(collect($months)->sum(fn (array $month) => $month['pending']))->toBe(1);
});

test('ranks the most used vehicles with the distance of completed trips', function () {
    $busy = Vehicle::factory()->create(['plate_number' => 'DT 1111 AA']);
    $quiet = Vehicle::factory()->create(['plate_number' => 'DT 2222 BB']);
    completedBooking($busy, '2026-05-01 08:00:00', 120, 12);
    completedBooking($busy, '2026-06-01 08:00:00', 80, 8);
    Booking::factory()->approved()->for($busy)->create(['start_at' => '2026-10-20 08:00:00', 'end_at' => '2026-10-20 10:00:00']);
    completedBooking($quiet, '2026-07-01 08:00:00', 300, 30);
    Booking::factory()->rejected()->for($quiet)->count(3)->create();

    $top = app(DashboardService::class)->admin(['year' => 2026, 'region_id' => null])['topVehicles'];

    expect($top)->toBe([
        ['id' => $busy->id, 'plate_number' => 'DT 1111 AA', 'name' => $busy->name, 'bookings' => 3, 'distance' => 200],
        ['id' => $quiet->id, 'plate_number' => 'DT 2222 BB', 'name' => $quiet->name, 'bookings' => 1, 'distance' => 300],
    ]);
});

test('limits the vehicle ranking to ten vehicles', function () {
    Vehicle::factory()->count(11)->create()->each(
        fn (Vehicle $vehicle) => Booking::factory()->approved()->for($vehicle)->create(),
    );

    expect(app(DashboardService::class)->admin(['year' => 2026, 'region_id' => null])['topVehicles'])->toHaveCount(10);
});

test('splits usage by vehicle type and ownership', function () {
    completedBooking(Vehicle::factory()->create(), '2026-05-01 08:00:00', 100, 10);
    Booking::factory()->approved()->for(Vehicle::factory()->cargo()->rented())->create();
    Booking::factory()->for(Vehicle::factory()->cargo())->create();

    $composition = app(DashboardService::class)->admin(['year' => 2026, 'region_id' => null])['composition'];

    expect($composition)->toBe([
        'type' => [
            ['key' => 'passenger', 'label' => 'Angkutan Orang', 'value' => 1],
            ['key' => 'cargo', 'label' => 'Angkutan Barang', 'value' => 1],
        ],
        'ownership' => [
            ['key' => 'owned', 'label' => 'Milik Perusahaan', 'value' => 1],
            ['key' => 'rented', 'label' => 'Sewa', 'value' => 1],
        ],
    ]);
});

test('sums distance and fuel per month from completed bookings only', function () {
    $vehicle = Vehicle::factory()->create();
    completedBooking($vehicle, '2026-05-01 08:00:00', 120, 12.5);
    completedBooking($vehicle, '2026-05-20 08:00:00', 80, 7.25);
    Booking::factory()->approved()->for($vehicle)->create(['start_at' => '2026-05-25 08:00:00', 'end_at' => '2026-05-25 10:00:00']);

    $months = app(DashboardService::class)->admin(['year' => 2026, 'region_id' => null])['distanceAndFuel'];

    expect($months[4])->toBe(['month' => 5, 'label' => 'Mei', 'distance' => 200, 'fuel' => 19.75])
        ->and($months[5])->toBe(['month' => 6, 'label' => 'Jun', 'distance' => 0, 'fuel' => 0.0]);
});

test('lists vehicles due for service within 14 days, most urgent first', function () {
    $overdue = Vehicle::factory()->create(['next_service_at' => '2026-10-10']);
    $soon = Vehicle::factory()->create(['next_service_at' => '2026-10-28']);
    Vehicle::factory()->create(['next_service_at' => '2026-10-29']);
    Vehicle::factory()->create(['next_service_at' => null]);

    $dashboard = app(DashboardService::class)->admin(['year' => 2026, 'region_id' => null]);

    expect(collect($dashboard['serviceDue'])->map(fn (array $vehicle) => Arr::only($vehicle, ['id', 'days_left']))->all())->toBe([
        ['id' => $overdue->id, 'days_left' => -4],
        ['id' => $soon->id, 'days_left' => 14],
    ])
        ->and($dashboard['stats']['vehicles_service_due'])->toBe(2);
});

test('summarizes bookings and vehicles for admins', function () {
    $vehicle = Vehicle::factory()->create();
    Vehicle::factory()->maintenance()->create();
    Booking::factory()->for($vehicle)->count(2)->create();
    Booking::factory()->approved()->for($vehicle)->create();
    Booking::factory()->completed()->for($vehicle)->create(['completed_at' => '2026-10-02 10:00:00']);
    Booking::factory()->completed()->for($vehicle)->create(['completed_at' => '2026-09-30 10:00:00']);

    $stats = app(DashboardService::class)->admin(['year' => 2026, 'region_id' => null])['stats'];

    expect($stats)->toBe([
        'total' => 5,
        'pending' => 2,
        'approved' => 1,
        'completed_this_month' => 1,
        'vehicles_available' => 1,
        'vehicles_total' => 2,
        'vehicles_service_due' => 0,
    ]);
});

test('filters every booking metric by the region of the vehicle pool', function () {
    $region = Region::factory()->create();
    $local = Vehicle::factory()->for(Location::factory()->for($region))->create();
    completedBooking($local, '2026-05-01 08:00:00', 150, 15);
    completedBooking(Vehicle::factory()->create(), '2026-05-01 08:00:00', 90, 9);

    $dashboard = app(DashboardService::class)->admin(['year' => 2026, 'region_id' => $region->id]);

    expect($dashboard['stats']['total'])->toBe(1)
        ->and($dashboard['stats']['vehicles_total'])->toBe(1)
        ->and($dashboard['topVehicles'])->toHaveCount(1)
        ->and($dashboard['topVehicles'][0]['id'])->toBe($local->id)
        ->and($dashboard['distanceAndFuel'][4]['distance'])->toBe(150);
});

test('summarizes the approvals of an approver', function () {
    [$me, $other] = User::factory()->approver()->count(2)->create();
    Booking::factory()->withApprovers($me, $other)->count(2)->create();
    Booking::factory()->withApprovers($other, $me)->create();
    Booking::factory()->approved()->state(['current_level' => 2])->withApprovers($me, $other)->create();
    Booking::factory()->rejected()->withApprovers($me, $other)->create();

    $dashboard = app(DashboardService::class)->approver($me, ['year' => 2026, 'region_id' => null]);

    expect($dashboard['stats'])->toBe(['waiting' => 2, 'approved' => 1, 'rejected' => 1])
        ->and($dashboard['waitingApprovals'])->toHaveCount(2);
});

test('shows only the five newest approvals waiting for the approver', function () {
    $me = User::factory()->approver()->create();
    $bookings = Booking::factory()
        ->count(6)
        ->sequence(fn ($sequence) => ['created_at' => now()->subDays(6 - $sequence->index)])
        ->withApprovers($me, User::factory()->approver()->create())
        ->create();

    $waiting = app(DashboardService::class)
        ->approver($me, ['year' => 2026, 'region_id' => null])['waitingApprovals']
        ->resolve();

    expect($waiting)->toHaveCount(5)
        ->and($waiting[0]['booking']['id'])->toBe($bookings->last()->id);
});

test('offers years from the first booking to the latest one or the current year', function () {
    Booking::factory()->create(['start_at' => '2024-02-01 08:00:00', 'end_at' => '2024-02-01 10:00:00']);

    expect(app(DashboardService::class)->availableYears())->toBe([2026, 2025, 2024]);
});
