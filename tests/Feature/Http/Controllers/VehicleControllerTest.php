<?php

use App\Models\Booking;
use App\Models\Location;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-14 08:00:00');
});

test('lists vehicles with their completed trips and days until service', function () {
    $vehicle = Vehicle::factory()->create(['plate_number' => 'DT 1234 AB', 'next_service_at' => '2026-10-18']);
    Booking::factory()->completed()->for($vehicle)->count(2)->create();
    Booking::factory()->approved()->for($vehicle)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('vehicles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/index')
            ->where('vehicles.data.0.plate_number', 'DT 1234 AB')
            ->where('vehicles.data.0.completed_bookings_count', 2)
            ->where('vehicles.data.0.days_until_service', 4)
            ->has('types', 2)
            ->has('ownerships', 2)
            ->has('statuses', 2)
            ->has('locations'));
});

test('filters vehicles by type, ownership, location, and status', function () {
    $location = Location::factory()->create();
    $match = Vehicle::factory()->cargo()->rented()->maintenance()->for($location)->create();
    Vehicle::factory()->cargo()->rented()->for($location)->create();
    Vehicle::factory()->cargo()->maintenance()->for($location)->create();
    Vehicle::factory()->rented()->maintenance()->for($location)->create();
    Vehicle::factory()->cargo()->rented()->maintenance()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('vehicles.index', [
            'type' => 'cargo',
            'ownership' => 'rented',
            'location_id' => $location->id,
            'status' => 'maintenance',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.id', $match->id));
});

test('shows the usage summary and the newest bookings of a vehicle', function () {
    $vehicle = Vehicle::factory()->create();
    Booking::factory()->completed()->for($vehicle)->create([
        'start_at' => '2026-09-01 08:00:00',
        'end_at' => '2026-09-01 12:00:00',
        'odometer_start' => 1_000,
        'odometer_end' => 1_150,
        'fuel_used' => 15,
    ]);
    Booking::factory()->completed()->for($vehicle)->create([
        'start_at' => '2026-09-10 08:00:00',
        'end_at' => '2026-09-10 12:00:00',
        'odometer_start' => 1_150,
        'odometer_end' => 1_250,
        'fuel_used' => 10,
    ]);
    $upcoming = Booking::factory()->approved()->for($vehicle)->create([
        'start_at' => '2026-10-20 08:00:00',
        'end_at' => '2026-10-20 12:00:00',
    ]);
    Booking::factory()->completed()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('vehicles/show')
            ->where('vehicle.id', $vehicle->id)
            ->where('summary', ['trips' => 2, 'distance' => 250, 'fuel' => 25, 'km_per_liter' => 10])
            ->has('bookings.data', 3)
            ->where('bookings.data.0.id', $upcoming->id));
});

test('leaves the fuel efficiency empty for a vehicle without completed trips', function () {
    $vehicle = Vehicle::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('vehicles.show', $vehicle))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary', ['trips' => 0, 'distance' => 0, 'fuel' => 0, 'km_per_liter' => null])
            ->has('bookings.data', 0));
});

test('forbids approvers from vehicle details', function () {
    $this->actingAs(User::factory()->approver()->create())
        ->get(route('vehicles.show', Vehicle::factory()->create()))
        ->assertForbidden();
});
