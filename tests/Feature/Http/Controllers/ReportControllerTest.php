<?php

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

beforeEach(function () {
    $this->travelTo('2026-10-14 08:00:00');
});

describe('index', function () {
    test('defaults to the current month', function () {
        Booking::factory()->create(['start_at' => '2026-10-20 08:00:00', 'end_at' => '2026-10-20 10:00:00']);
        Booking::factory()->create(['start_at' => '2026-11-02 08:00:00', 'end_at' => '2026-11-02 10:00:00']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('filters', [
                    'date_from' => '2026-10-01',
                    'date_to' => '2026-10-31',
                    'status' => null,
                    'vehicle_type' => null,
                    'region_id' => null,
                ])
                ->where('summary.total', 1)
                ->has('bookings.data', 1)
                ->has('statuses', 5)
                ->has('vehicleTypes', 2));
    });

    test('previews 15 bookings per page and keeps the filters in the links', function () {
        Booking::factory()->count(16)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.index', ['date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'status' => 'pending']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('bookings.data', 15)
                ->where('bookings.meta.total', 16)
                ->where('bookings.links.next', route('reports.index', [
                    'date_from' => '2026-10-01',
                    'date_to' => '2026-10-31',
                    'status' => 'pending',
                    'page' => 2,
                ])));
    });

    test('redirects an inverted date range to the default report', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.index', ['date_from' => '2026-10-31', 'date_to' => '2026-10-01']))
            ->assertRedirect(route('reports.index'));
    });
});

describe('export', function () {
    test('downloads an xlsx file named after the period', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('reports.export', ['date_from' => '2026-10-01', 'date_to' => '2026-10-31']))
            ->assertOk()
            ->assertDownload('laporan-pemesanan_2026-10-01_2026-10-31.xlsx')
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    test('writes the filtered bookings and a summary sheet', function () {
        $admin = User::factory()->admin()->create(['name' => 'Admin Pool']);
        [$first, $second] = User::factory()->approver()->sequence(['name' => 'Budi Santoso'], ['name' => 'Siti Rahmawati'])->count(2)->create();
        $vehicle = Vehicle::factory()->create(['plate_number' => 'DT 1234 AB']);
        $booking = Booking::factory()->completed()->for($vehicle)->state(['current_level' => 2])
            ->withApprovers($first, $second)
            ->create([
                'start_at' => '2026-10-05 08:00:00',
                'end_at' => '2026-10-05 12:00:00',
                'odometer_start' => 5_000,
                'odometer_end' => 5_125,
                'fuel_used' => 14.5,
            ]);
        Booking::factory()->rejected()->create(['start_at' => '2026-10-06 08:00:00', 'end_at' => '2026-10-06 12:00:00']);
        Booking::factory()->create(['start_at' => '2026-11-06 08:00:00', 'end_at' => '2026-11-06 12:00:00']);

        $response = $this->actingAs($admin)
            ->get(route('reports.export', ['date_from' => '2026-10-01', 'date_to' => '2026-10-31']));

        expect($response->baseResponse)->toBeInstanceOf(BinaryFileResponse::class);
        $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());

        expect($spreadsheet->getSheetNames())->toBe(['Pemesanan', 'Ringkasan']);

        $bookings = $spreadsheet->getSheetByName('Pemesanan');
        expect($bookings->getHighestRow())->toBe(3)
            ->and($bookings->getFreezePane())->toBe('A2')
            ->and($bookings->getAutoFilter()->getRange())->toBe('A1:U3')
            ->and($bookings->getStyle('A1')->getFont()->getBold())->toBeTrue()
            ->and($bookings->getCell('B2')->getValue())->toBe($booking->code)
            ->and($bookings->getCell('H2')->getValue())->toBe('DT 1234 AB')
            ->and($bookings->getCell('Q2')->getValue())->toBe('Selesai')
            ->and($bookings->getCell('R2')->getValue())->toBe('2/2')
            ->and($bookings->getCell('S2')->getValue())->toStartWith('L1: Budi Santoso – Disetujui')
            ->and($bookings->getCell('T2')->getValue())->toBe(125)
            ->and($bookings->getCell('U2')->getValue())->toBe(14.5)
            ->and($bookings->getStyle('O2')->getNumberFormat()->getFormatCode())->toBe('dd/mm/yyyy hh:mm')
            ->and($bookings->getCell('O2')->getFormattedValue())->toBe('05/10/2026 08:00');

        $summary = collect($spreadsheet->getSheetByName('Ringkasan')->toArray())
            ->mapWithKeys(fn (array $row) => [(string) $row[0] => $row[1]]);
        expect($summary['Periode'])->toBe('1 Okt 2026 – 31 Okt 2026')
            ->and($summary['Dicetak oleh'])->toBe('Admin Pool')
            ->and($summary['Selesai'])->toBe('1')
            ->and($summary['Ditolak'])->toBe('1')
            ->and($summary['Menunggu Persetujuan'])->toBe('0')
            ->and($summary['Total'])->toBe('2')
            ->and($summary['Total km tempuh'])->toBe('125')
            ->and($summary['Total BBM (L)'])->toBe('14.50');
    });

    test('logs the export with the filters used', function () {
        $admin = User::factory()->admin()->create(['name' => 'Admin Pool']);

        $this->actingAs($admin)
            ->get(route('reports.export', ['date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'status' => 'completed']))
            ->assertOk();

        $log = ActivityLog::where('action', 'report.exported')->sole();
        expect($log)
            ->user_id->toBe($admin->id)
            ->description->toBe('Admin Pool mengekspor laporan pemesanan 1 Okt 2026 – 31 Okt 2026')
            ->properties->toBe([
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-31',
                'status' => 'completed',
                'vehicle_type' => null,
                'region_id' => null,
            ]);
    });

    test('forbids approvers from exporting', function () {
        $this->actingAs(User::factory()->approver()->create())
            ->get(route('reports.export'))
            ->assertForbidden();

        expect(ActivityLog::where('action', 'report.exported')->exists())->toBeFalse();
    });
});
