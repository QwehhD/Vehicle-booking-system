<?php

use App\Models\Booking;
use App\Models\Region;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-14 08:00:00');
});

test('shows the admin dashboard for the current year by default', function () {
    $region = Region::factory()->create(['name' => 'Sulawesi Tenggara']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('filters', ['year' => 2026, 'region_id' => null])
            ->where('years', [2026])
            ->where('regions', [['id' => $region->id, 'name' => 'Sulawesi Tenggara']])
            ->where('dashboard.view', 'admin')
            ->has('dashboard.bookingsPerMonth', 12)
            ->has('dashboard.distanceAndFuel', 12)
            ->has('dashboard.composition.type', 2)
            ->has('dashboard.serviceDue')
            ->has('dashboard.stats'));
});

test('shows the approver dashboard with their waiting approvals', function () {
    [$me, $other] = User::factory()->approver()->count(2)->create();
    $booking = Booking::factory()->withApprovers($me, $other)->create();

    $this->actingAs($me)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.view', 'approver')
            ->where('dashboard.stats', ['waiting' => 1, 'approved' => 0, 'rejected' => 0])
            ->where('dashboard.waitingApprovals.0.booking.id', $booking->id)
            ->has('dashboard.bookingsPerMonth', 12)
            ->has('dashboard.topVehicles')
            ->missing('dashboard.serviceDue'));
});

test('applies the year and region filters', function () {
    $region = Region::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard', ['year' => 2025, 'region_id' => $region->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['year' => 2025, 'region_id' => $region->id]));
});

test('redirects invalid filters to the unfiltered dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard', ['year' => 'kemarin']))
        ->assertRedirect(route('dashboard'));
});
