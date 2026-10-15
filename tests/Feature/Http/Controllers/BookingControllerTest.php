<?php

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\VehicleType;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Region;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-14 08:00:00');
});

/**
 * Payload pemesanan valid; kendaraan, driver, dan dua approver dibuat bila
 * tidak diberikan.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bookingPayload(array $overrides = []): array
{
    return [
        'requester_name' => 'Dewi Kartika',
        'requester_department' => 'K3 & Lingkungan',
        'purpose' => 'Inspeksi K3 rutin area pit penambangan',
        'destination' => 'Pit Blok A Pomalaa',
        'start_at' => '2026-10-20 08:00:00',
        'end_at' => '2026-10-20 15:00:00',
        'vehicle_id' => $overrides['vehicle_id'] ?? Vehicle::factory()->create()->id,
        'driver_id' => $overrides['driver_id'] ?? Driver::factory()->create()->id,
        'approvers' => $overrides['approvers'] ?? User::factory()->approver()->count(2)->create()->modelKeys(),
        ...$overrides,
    ];
}

describe('index', function () {
    test('lists the newest bookings first, 15 per page', function () {
        $admin = User::factory()->admin()->create();
        $bookings = Booking::factory()
            ->count(16)
            ->sequence(fn ($sequence) => ['created_at' => now()->subDays(16 - $sequence->index)])
            ->create();

        $this->actingAs($admin)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('bookings/index')
                ->has('bookings.data', 15)
                ->where('bookings.data.0.id', $bookings->last()->id)
                ->where('bookings.meta.total', 16)
                ->where('bookings.data.0.approval_progress', ['approved' => 0, 'total' => 0]));
    });

    test('shows the approval progress of each booking', function () {
        $admin = User::factory()->admin()->create();
        [$first, $second] = User::factory()->approver()->count(2)->create();
        Booking::factory()->state(['current_level' => 2])->withApprovers($first, $second)->create();

        $this->actingAs($admin)
            ->get(route('bookings.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('bookings.data.0.approval_progress', ['approved' => 1, 'total' => 2]));
    });

    test('searches by code, requester, or plate number', function (string $field) {
        $admin = User::factory()->admin()->create();
        $match = Booking::factory()
            ->for(Vehicle::factory()->state(['plate_number' => 'DT 4321 ZZ']))
            ->create(['requester_name' => 'Yohanes Pasanda']);
        Booking::factory()->create(['requester_name' => 'Nur Aisyah']);

        $term = match ($field) {
            'code' => $match->code,
            'requester' => 'pasanda',
            'plate' => '4321',
        };

        $this->actingAs($admin)
            ->get(route('bookings.index', ['search' => $term]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.id', $match->id)
                ->where('filters.search', $term));
    })->with(['code', 'requester', 'plate']);

    test('treats the search term as plain text', function (string $term) {
        $admin = User::factory()->admin()->create();
        Booking::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('bookings.index', ['search' => $term]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('bookings.data', 0));
    })->with([
        'sql comment' => "' OR 1=1 --",
        'like wildcard only' => '%%',
        'underscore' => '___',
    ]);

    test('filters by status, vehicle type, and region', function () {
        $admin = User::factory()->admin()->create();
        $region = Region::factory()->create();
        $cargoInRegion = Vehicle::factory()->cargo()->for(Location::factory()->for($region))->create();

        $match = Booking::factory()->approved()->for($cargoInRegion)->create();
        Booking::factory()->for($cargoInRegion)->create();
        Booking::factory()->approved()->for(Vehicle::factory()->for(Location::factory()->for($region)))->create();
        Booking::factory()->approved()->for(Vehicle::factory()->cargo())->create();

        $this->actingAs($admin)
            ->get(route('bookings.index', [
                'status' => BookingStatus::Approved->value,
                'vehicle_type' => VehicleType::Cargo->value,
                'region_id' => $region->id,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('bookings.data', 1)
                ->where('bookings.data.0.id', $match->id));
    });

    test('filters by the start date range inclusively', function () {
        $admin = User::factory()->admin()->create();
        Booking::factory()->create(['start_at' => '2026-10-19 23:00:00', 'end_at' => '2026-10-20 02:00:00']);
        $first = Booking::factory()->create(['start_at' => '2026-10-20 00:00:00', 'end_at' => '2026-10-20 05:00:00']);
        $last = Booking::factory()->create(['start_at' => '2026-10-22 23:30:00', 'end_at' => '2026-10-23 02:00:00']);
        Booking::factory()->create(['start_at' => '2026-10-23 00:00:00', 'end_at' => '2026-10-23 05:00:00']);

        $this->actingAs($admin)
            ->get(route('bookings.index', ['date_from' => '2026-10-20', 'date_to' => '2026-10-22']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('bookings.data', 2)
                ->where('bookings.data.0.id', $last->id)
                ->where('bookings.data.1.id', $first->id));
    });

    test('keeps the filters in the pagination links', function () {
        $admin = User::factory()->admin()->create();
        Booking::factory()->count(16)->create();

        $this->actingAs($admin)
            ->get(route('bookings.index', ['status' => 'pending']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('bookings.links.next', route('bookings.index', ['status' => 'pending', 'page' => 2])));
    });

    test('redirects invalid filters to the unfiltered list', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('bookings.index', ['status' => 'unknown']))
            ->assertRedirect(route('bookings.index'));
    });
});

describe('create', function () {
    test('offers only available vehicles, active drivers, and approvers', function () {
        $admin = User::factory()->admin()->create();
        $location = Location::factory()->create(['name' => 'Tambang Pomalaa']);
        $vehicle = Vehicle::factory()->rented()->for($location)->create([
            'plate_number' => 'DT 1234 AB',
            'name' => 'Toyota Hilux',
        ]);
        Vehicle::factory()->maintenance()->create();
        $driver = Driver::factory()->create();
        Driver::factory()->inactive()->create();
        $approver = User::factory()->approver()->create(['position' => 'Supervisor Pool']);

        $this->actingAs($admin)
            ->get(route('bookings.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('bookings/create')
                ->where('vehicles', [[
                    'value' => $vehicle->id,
                    'label' => 'DT 1234 AB — Toyota Hilux · Angkutan Orang · Sewa · Tambang Pomalaa',
                ]])
                ->where('drivers.0.value', $driver->id)
                ->has('drivers', 1)
                ->where('approvers', [[
                    'value' => $approver->id,
                    'label' => $approver->name,
                    'description' => 'Supervisor Pool',
                ]]));
    });
});

describe('store', function () {
    test('creates a pending booking with ordered approvals and logs it', function () {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create(['plate_number' => 'DT 1234 AB']);
        [$first, $second, $third] = User::factory()->approver()->count(3)->create();

        $response = $this->actingAs($admin)->post(route('bookings.store'), bookingPayload([
            'vehicle_id' => $vehicle->id,
            'approvers' => [$third->id, $first->id, $second->id],
        ]));

        $booking = Booking::with('approvals')->sole();

        $response->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHas('success', "Pemesanan {$booking->code} berhasil dibuat.");

        expect($booking)
            ->status->toBe(BookingStatus::Pending)
            ->current_level->toBe(1)
            ->created_by->toBe($admin->id)
            ->code->toBe(sprintf('BK-202610-%05d', $booking->id))
            ->and($booking->approvals->map->only('level', 'approver_id', 'status')->all())->toBe([
                ['level' => 1, 'approver_id' => $third->id, 'status' => ApprovalStatus::Pending],
                ['level' => 2, 'approver_id' => $first->id, 'status' => ApprovalStatus::Pending],
                ['level' => 3, 'approver_id' => $second->id, 'status' => ApprovalStatus::Pending],
            ]);

        $log = ActivityLog::where('action', 'booking.created')->sole();
        expect($log)
            ->user_id->toBe($admin->id)
            ->subject_type->toBe('booking')
            ->subject_id->toBe($booking->id)
            ->description->toBe("{$admin->name} membuat pemesanan {$booking->code} untuk Dewi Kartika (DT 1234 AB)")
            ->and($log->properties['approvers'])->toBe([$third->name, $first->name, $second->name]);
    });

    test('ignores fields that are not part of the form', function () {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('bookings.store'), bookingPayload([
            'status' => 'approved',
            'current_level' => 3,
            'code' => 'BK-HACK-00001',
            'created_by' => $other->id,
            'odometer_end' => 99_999,
            'completed_at' => '2026-10-01 08:00:00',
        ]))->assertSessionHasNoErrors();

        expect(Booking::sole())
            ->status->toBe(BookingStatus::Pending)
            ->current_level->toBe(1)
            ->code->toStartWith('BK-202610-')
            ->created_by->toBe($admin->id)
            ->odometer_end->toBeNull()
            ->completed_at->toBeNull();
    });

    test('rejects invalid input', function (Closure $overrides, string $field, string $message) {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('bookings.store'), bookingPayload($overrides()))
            ->assertSessionHasErrors([$field => $message]);

        expect(Booking::count())->toBe(0);
    })->with([
        'fewer than two approvers' => [
            fn () => ['approvers' => [User::factory()->approver()->create()->id]],
            'approvers',
            'Pilih minimal 2 approver.',
        ],
        'more than five approvers' => [
            fn () => ['approvers' => User::factory()->approver()->count(6)->create()->modelKeys()],
            'approvers',
            'Pilih maksimal 5 approver.',
        ],
        'duplicate approvers' => [
            function () {
                $approver = User::factory()->approver()->create();

                return ['approvers' => [$approver->id, $approver->id]];
            },
            'approvers.1',
            'Approver ini sudah dipilih di level lain.',
        ],
        'an admin as approver' => [
            fn () => ['approvers' => [User::factory()->admin()->create()->id, User::factory()->approver()->create()->id]],
            'approvers.0',
            'Pengguna ini bukan approver.',
        ],
        'end equal to start' => [
            fn () => ['start_at' => '2026-10-20 08:00:00', 'end_at' => '2026-10-20 08:00:00'],
            'end_at',
            'Waktu selesai harus setelah waktu mulai.',
        ],
        'end before start' => [
            fn () => ['start_at' => '2026-10-20 08:00:00', 'end_at' => '2026-10-20 07:00:00'],
            'end_at',
            'Waktu selesai harus setelah waktu mulai.',
        ],
        'start in the past' => [
            fn () => ['start_at' => '2026-10-14 07:00:00', 'end_at' => '2026-10-14 10:00:00'],
            'start_at',
            'Waktu mulai harus setelah waktu sekarang.',
        ],
        'vehicle under maintenance' => [
            fn () => ['vehicle_id' => Vehicle::factory()->maintenance()->create()->id],
            'vehicle_id',
            'Kendaraan tidak ditemukan atau sedang dalam perawatan.',
        ],
        'inactive driver' => [
            fn () => ['driver_id' => Driver::factory()->inactive()->create()->id],
            'driver_id',
            'Driver tidak ditemukan atau sedang nonaktif.',
        ],
    ]);

    test('requires every text field', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('bookings.store'))
            ->assertSessionHasErrors([
                'requester_name' => 'Nama pemohon wajib diisi.',
                'requester_department' => 'Departemen wajib diisi.',
                'purpose' => 'Keperluan wajib diisi.',
                'destination' => 'Tujuan wajib diisi.',
                'start_at' => 'Waktu mulai wajib diisi.',
                'end_at' => 'Waktu selesai wajib diisi.',
                'vehicle_id' => 'Kendaraan wajib diisi.',
                'driver_id' => 'Driver wajib diisi.',
                'approvers' => 'Pilih minimal 2 approver.',
            ]);
    });

    test('rejects a vehicle that is already booked for an overlapping schedule', function (string $status) {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create(['plate_number' => 'DT 1234 AB']);
        $existing = Booking::factory()->for($vehicle)->create([
            'status' => $status,
            'start_at' => '2026-10-20 13:00:00',
            'end_at' => '2026-10-20 18:00:00',
        ]);

        $this->actingAs($admin)
            ->post(route('bookings.store'), bookingPayload(['vehicle_id' => $vehicle->id]))
            ->assertSessionHasErrors([
                'vehicle_id' => "Kendaraan DT 1234 AB sudah dipesan untuk {$existing->code} (20 Okt 2026, 13:00 – 20 Okt 2026, 18:00).",
            ]);

        expect(Booking::count())->toBe(1);
    })->with(['pending', 'approved']);

    test('rejects a driver who is already assigned to an overlapping schedule', function () {
        $admin = User::factory()->admin()->create();
        $driver = Driver::factory()->create(['name' => 'Rahmat Hidayat']);
        $existing = Booking::factory()->approved()->for($driver)->create([
            'start_at' => '2026-10-20 06:00:00',
            'end_at' => '2026-10-20 09:00:00',
        ]);

        $this->actingAs($admin)
            ->post(route('bookings.store'), bookingPayload(['driver_id' => $driver->id]))
            ->assertSessionHasErrors([
                'driver_id' => "Driver Rahmat Hidayat sudah bertugas untuk {$existing->code} (20 Okt 2026, 06:00 – 20 Okt 2026, 09:00).",
            ]);
    });

    test('allows schedules that only touch an existing booking', function () {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();
        $driver = Driver::factory()->create();
        Booking::factory()->for($vehicle)->for($driver)->create([
            'start_at' => '2026-10-20 05:00:00',
            'end_at' => '2026-10-20 08:00:00',
        ]);
        Booking::factory()->approved()->for($vehicle)->for($driver)->create([
            'start_at' => '2026-10-20 15:00:00',
            'end_at' => '2026-10-20 18:00:00',
        ]);

        $this->actingAs($admin)
            ->post(route('bookings.store'), bookingPayload(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]))
            ->assertSessionHasNoErrors();

        expect(Booking::count())->toBe(3);
    });

    test('ignores overlapping bookings that no longer hold the schedule', function (string $state) {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();
        $driver = Driver::factory()->create();
        Booking::factory()->{$state}()->for($vehicle)->for($driver)->create([
            'start_at' => '2026-10-20 09:00:00',
            'end_at' => '2026-10-20 12:00:00',
        ]);

        $this->actingAs($admin)
            ->post(route('bookings.store'), bookingPayload(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]))
            ->assertSessionHasNoErrors();

        expect(Booking::count())->toBe(2);
    })->with(['rejected', 'cancelled', 'completed']);

    test('forbids approvers from creating bookings', function () {
        $approver = User::factory()->approver()->create();

        $this->actingAs($approver)
            ->post(route('bookings.store'), bookingPayload())
            ->assertForbidden();

        expect(Booking::count())->toBe(0);
    });
});

describe('show', function () {
    test('shows the booking with its approval timeline and activity logs', function () {
        $admin = User::factory()->admin()->create();
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->state(['current_level' => 2])->withApprovers($first, $second)->create();
        ActivityLog::factory()->for($admin)->for($booking, 'subject')->create(['description' => 'Log lama', 'created_at' => now()->subDay()]);
        ActivityLog::factory()->for($first)->for($booking, 'subject')->create(['description' => 'Log baru']);
        ActivityLog::factory()->for($admin)->create();

        $this->actingAs($admin)
            ->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('bookings/show')
                ->where('booking.code', $booking->code)
                ->where('booking.approval_progress', ['approved' => 1, 'total' => 2])
                ->where('booking.approvals.0.approver.id', $first->id)
                ->where('booking.approvals.0.status', 'approved')
                ->where('booking.approvals.1.approver.id', $second->id)
                ->where('booking.approvals.1.status', 'pending')
                ->has('activityLogs', 2)
                ->where('activityLogs.0.description', 'Log baru')
                ->where('can', ['cancel' => true, 'complete' => false]));
    });

    test('shows ip addresses and user agents of the activity logs only to admins', function () {
        $admin = User::factory()->admin()->create();
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->withApprovers($first, $second)->create();
        ActivityLog::factory()->for($admin)->for($booking, 'subject')->create([
            'ip_address' => '10.20.1.11',
            'user_agent' => 'AdminBrowser/1.0',
        ]);

        $this->actingAs($admin)
            ->get(route('bookings.show', $booking))
            ->assertInertia(fn (Assert $page) => $page
                ->where('activityLogs.0.ip_address', '10.20.1.11')
                ->where('activityLogs.0.user_agent', 'AdminBrowser/1.0'));

        $this->actingAs($first)
            ->get(route('bookings.show', $booking))
            ->assertInertia(fn (Assert $page) => $page
                ->where('activityLogs.0.ip_address', null)
                ->where('activityLogs.0.user_agent', null));
    });

    test('lets an approver of the booking view it without admin actions', function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->approved()->state(['current_level' => 2])->withApprovers($first, $second)->create();

        $this->actingAs($second)
            ->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('can', ['cancel' => false, 'complete' => false])
                ->where('actionableApproval', null));
    });

    test('offers approval actions only to the approver whose turn it is', function () {
        [$first, $second] = User::factory()->approver()->count(2)->create();
        $booking = Booking::factory()->withApprovers($first, $second)->create();
        $approval = $booking->approvals()->where('level', 1)->sole();

        $this->actingAs($first)
            ->get(route('bookings.show', $booking))
            ->assertInertia(fn (Assert $page) => $page
                ->where('actionableApproval', ['id' => $approval->id, 'level' => 1, 'total_levels' => 2])
                ->missing('booking.approvals.0.booking'));

        $this->actingAs($second)
            ->get(route('bookings.show', $booking))
            ->assertInertia(fn (Assert $page) => $page->where('actionableApproval', null));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('bookings.show', $booking))
            ->assertInertia(fn (Assert $page) => $page->where('actionableApproval', null));
    });

    test('forbids approvers who are not part of the booking', function () {
        $booking = Booking::factory()->withApprovers(...User::factory()->approver()->count(2)->create())->create();

        $this->actingAs(User::factory()->approver()->create())
            ->get(route('bookings.show', $booking))
            ->assertForbidden();
    });
});

describe('cancel', function () {
    test('cancels a pending booking and skips the pending approvals', function () {
        $admin = User::factory()->admin()->create();
        [$first, $second, $third] = User::factory()->approver()->count(3)->create();
        $booking = Booking::factory()->state(['current_level' => 2])->withApprovers($first, $second, $third)->create();

        $this->actingAs($admin)
            ->from(route('bookings.show', $booking))
            ->patch(route('bookings.cancel', $booking), ['cancel_reason' => 'Kegiatan ditunda oleh pemohon.'])
            ->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHas('success', "Pemesanan {$booking->code} dibatalkan.");

        $booking->refresh()->load('approvals');

        expect($booking)
            ->status->toBe(BookingStatus::Cancelled)
            ->cancel_reason->toBe('Kegiatan ditunda oleh pemohon.')
            ->cancelled_at->toEqual(now())
            ->and($booking->approvals->pluck('status')->all())->toBe([
                ApprovalStatus::Approved,
                ApprovalStatus::Skipped,
                ApprovalStatus::Skipped,
            ]);

        $log = ActivityLog::where('action', 'booking.cancelled')->sole();
        expect($log)
            ->subject_id->toBe($booking->id)
            ->description->toBe("{$admin->name} membatalkan {$booking->code}")
            ->properties->toBe(['reason' => 'Kegiatan ditunda oleh pemohon.', 'previous_status' => 'pending']);
    });

    test('cancels an approved booking', function () {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->approved()->state(['current_level' => 2])
            ->withApprovers(...User::factory()->approver()->count(2)->create())
            ->create();

        $this->actingAs($admin)
            ->patch(route('bookings.cancel', $booking), ['cancel_reason' => 'Pemohon berangkat bersama tim lain.'])
            ->assertSessionHasNoErrors();

        expect($booking->refresh()->status)->toBe(BookingStatus::Cancelled)
            ->and($booking->approvals()->pluck('status')->unique()->all())->toBe([ApprovalStatus::Approved]);
    });

    test('requires a cancellation reason', function () {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create();

        $this->actingAs($admin)
            ->patch(route('bookings.cancel', $booking), ['cancel_reason' => ''])
            ->assertSessionHasErrors(['cancel_reason' => 'Alasan pembatalan wajib diisi.']);

        expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
    });

    test('forbids cancelling a booking that is already finished', function (string $state) {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->{$state}()->create();

        $this->actingAs($admin)
            ->patch(route('bookings.cancel', $booking), ['cancel_reason' => 'Tidak jadi.'])
            ->assertForbidden();

        expect(ActivityLog::where('action', 'booking.cancelled')->exists())->toBeFalse();
    })->with(['rejected', 'cancelled', 'completed']);
});

describe('complete', function () {
    test('completes an approved booking and updates the vehicle odometer', function () {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create(['odometer' => 84_120]);
        $booking = Booking::factory()->approved()->for($vehicle)->create();

        $this->actingAs($admin)
            ->from(route('bookings.show', $booking))
            ->patch(route('bookings.complete', $booking), [
                'odometer_start' => 84_120,
                'odometer_end' => 84_245,
                'fuel_used' => '14.75',
            ])
            ->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHas('success', "Pemesanan {$booking->code} diselesaikan.");

        expect($booking->refresh())
            ->status->toBe(BookingStatus::Completed)
            ->odometer_start->toBe(84_120)
            ->odometer_end->toBe(84_245)
            ->fuel_used->toBe('14.75')
            ->completed_at->toEqual(now())
            ->and($vehicle->refresh()->odometer)->toBe(84_245);

        $log = ActivityLog::where('action', 'booking.completed')->sole();
        expect($log)
            ->description->toBe("{$admin->name} menyelesaikan {$booking->code} (125 km, 14,75 L)")
            ->properties->toBe(['odometer_start' => 84_120, 'odometer_end' => 84_245, 'distance' => 125, 'fuel_used' => 14.75]);
    });

    test('never moves the vehicle odometer backwards', function () {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create(['odometer' => 90_000]);
        $booking = Booking::factory()->approved()->for($vehicle)->create();

        $this->actingAs($admin)
            ->patch(route('bookings.complete', $booking), [
                'odometer_start' => 84_120,
                'odometer_end' => 84_245,
                'fuel_used' => 10,
            ])
            ->assertSessionHasNoErrors();

        expect($booking->refresh()->odometer_end)->toBe(84_245)
            ->and($vehicle->refresh()->odometer)->toBe(90_000);
    });

    test('only completes approved bookings', function (string $status) {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create(['status' => $status]);

        $this->actingAs($admin)
            ->patch(route('bookings.complete', $booking), [
                'odometer_start' => 1_000,
                'odometer_end' => 1_100,
                'fuel_used' => 10,
            ])
            ->assertForbidden();

        expect($booking->refresh()->status->value)->toBe($status);
    })->with(['pending', 'rejected', 'cancelled', 'completed']);

    test('rejects an end odometer lower than the start odometer', function () {
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create(['odometer' => 84_120]);
        $booking = Booking::factory()->approved()->for($vehicle)->create();

        $this->actingAs($admin)
            ->patch(route('bookings.complete', $booking), [
                'odometer_start' => 84_120,
                'odometer_end' => 84_000,
                'fuel_used' => 10,
            ])
            ->assertSessionHasErrors(['odometer_end' => 'Odometer akhir tidak boleh lebih kecil dari odometer awal.']);

        expect($booking->refresh()->status)->toBe(BookingStatus::Approved)
            ->and($vehicle->refresh()->odometer)->toBe(84_120);
    });

    test('requires fuel usage above zero', function () {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->approved()->create();

        $this->actingAs($admin)
            ->patch(route('bookings.complete', $booking), [
                'odometer_start' => 1_000,
                'odometer_end' => 1_100,
                'fuel_used' => 0,
            ])
            ->assertSessionHasErrors(['fuel_used' => 'BBM terpakai harus lebih dari 0 liter.']);
    });
});
