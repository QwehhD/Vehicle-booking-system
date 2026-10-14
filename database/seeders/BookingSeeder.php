<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\Region;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data pemesanan demo dari 6 bulan lalu sampai 2 minggu ke depan, relatif
 * terhadap now(), tanpa jadwal kendaraan atau driver yang bertabrakan.
 *
 * @phpstan-type Plan array{
 *     vehicle: Vehicle,
 *     driver: Driver,
 *     approvers: list<User>,
 *     status: BookingStatus,
 *     current_level: int,
 *     cancelled_after_approval: bool,
 *     requester: array{0: string, 1: string},
 *     purpose: string,
 *     destination: string,
 *     start_at: CarbonImmutable,
 *     end_at: CarbonImmutable,
 *     created_at: CarbonImmutable,
 *     distance: int|null,
 *     odometer_start: int|null,
 * }
 */
class BookingSeeder extends Seeder
{
    private const PAST_BOOKINGS = 68;

    /**
     * Status booking mendatang beserta level yang sedang menunggu giliran.
     *
     * @var list<array{0: BookingStatus, 1: int|null}>
     */
    private const UPCOMING_BOOKINGS = [
        [BookingStatus::Approved, null],
        [BookingStatus::Approved, null],
        [BookingStatus::Approved, null],
        [BookingStatus::Approved, null],
        [BookingStatus::Approved, null],
        [BookingStatus::Approved, null],
        [BookingStatus::Pending, 1],
        [BookingStatus::Pending, 1],
        [BookingStatus::Pending, 1],
        [BookingStatus::Pending, 2],
        [BookingStatus::Pending, 2],
        [BookingStatus::Pending, 2],
    ];

    /**
     * @var list<array{0: string, 1: string}>
     */
    private const REQUESTERS = [
        ['Hasanuddin Rauf', 'Eksplorasi'],
        ['Dewi Kartika', 'K3 & Lingkungan'],
        ['Fajar Nugroho', 'Operasional Tambang'],
        ['Rina Marlina', 'Human Capital'],
        ['Bambang Wijaya', 'Logistik'],
        ['Nur Aisyah', 'Keuangan'],
        ['Yohanes Pasanda', 'Geologi'],
        ['Asrul Mappanyukki', 'Maintenance Alat Berat'],
        ['Sri Wahyuni', 'Community Development'],
        ['Taufik Ismail', 'Operasional Tambang'],
        ['Maria Lumban Gaol', 'K3 & Lingkungan'],
        ['Irfan Hakim', 'Logistik'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const PURPOSES = [
        'passenger' => [
            'Antar tim eksplorasi ke lokasi titik bor baru',
            'Inspeksi K3 rutin area pit penambangan',
            'Mendampingi kunjungan auditor lingkungan',
            'Rapat koordinasi dengan pemerintah daerah',
            'Penjemputan karyawan rotasi dari bandara',
            'Survei jalan hauling bersama kontraktor',
            'Sosialisasi program CSR di desa lingkar tambang',
        ],
        'cargo' => [
            'Pengiriman suku cadang alat berat ke site',
            'Distribusi pelumas dan filter untuk workshop',
            'Pengangkutan sampel ore ke laboratorium',
            'Pengiriman logistik dan bahan makanan camp',
            'Pengangkutan material perbaikan jalan hauling',
        ],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const DESTINATIONS = [
        'Sulawesi Tenggara' => [
            'Bandara Haluoleo, Kendari',
            'Pelabuhan Kolaka',
            'Pit Blok A Pomalaa',
            'Kantor Bupati Konawe Utara',
            'Jetty Pomalaa',
            'Laboratorium Geologi Kendari',
            'Desa Hakatutobu, Pomalaa',
        ],
        'Sulawesi Tengah' => [
            'Kawasan Industri Morowali',
            'Pelabuhan Bahodopi',
            'Pit Bahodopi Timur',
            'Bandara Morowali',
            'Kolonodale',
            'Kantor Bupati Morowali Utara',
        ],
        'Maluku Utara' => [
            'Pelabuhan Maba',
            'Bandara Buli',
            'Pit Blok C Halmahera Timur',
            'Kota Maba',
            'Desa Wayamli',
        ],
    ];

    private const APPROVE_NOTES = [
        'Silakan, pastikan driver cukup istirahat.',
        'Disetujui, mohon patuhi batas kecepatan di jalan tambang.',
        'Oke, kembalikan kendaraan dalam kondisi bersih.',
    ];

    private const REJECT_NOTES = [
        'Kendaraan dibutuhkan untuk kegiatan prioritas di site.',
        'Mohon lampirkan surat tugas terlebih dahulu.',
        'Gunakan kendaraan sewa, unit ini akan masuk jadwal service.',
        'Kegiatan dapat digabung dengan perjalanan tim lain di hari yang sama.',
    ];

    private const CANCEL_REASONS = [
        'Kegiatan ditunda oleh pemohon.',
        'Jadwal rapat dipindah ke minggu depan.',
        'Pemohon berangkat bersama rombongan tim lain.',
        'Cuaca buruk, akses jalan tambang ditutup sementara.',
    ];

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36';

    private CarbonImmutable $now;

    private User $admin;

    /**
     * @var array<int, string>
     */
    private array $regionNames = [];

    /**
     * Bobot pemilihan kendaraan agar frekuensi pemakaian bervariasi.
     *
     * @var array<int, int>
     */
    private array $vehicleWeights = [];

    /**
     * Jadwal yang sudah terpakai per kendaraan dan per driver.
     *
     * @var array<string, list<array{0: CarbonImmutable, 1: CarbonImmutable}>>
     */
    private array $schedules = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->now = now()->startOfMinute();
        $this->admin = User::where('email', 'admin@nikel.test')->firstOrFail();
        $this->regionNames = Region::pluck('name', 'id')->all();

        $approvers = User::whereIn('email', ['approver1@nikel.test', 'approver2@nikel.test', 'approver3@nikel.test'])
            ->get()
            ->keyBy('email');

        $vehicles = Vehicle::with('location')->get();
        $drivers = Driver::with('location')->get();

        foreach ($vehicles as $vehicle) {
            $this->vehicleWeights[$vehicle->id] = $vehicle->status === VehicleStatus::Maintenance
                ? 1
                : fake()->numberBetween(2, 7);
        }

        $chains = [
            'default' => [$approvers['approver1@nikel.test'], $approvers['approver2@nikel.test']],
            'branch' => [$approvers['approver3@nikel.test'], $approvers['approver2@nikel.test']],
            'escalated' => [$approvers['approver1@nikel.test'], $approvers['approver3@nikel.test'], $approvers['approver2@nikel.test']],
        ];

        $plans = [];

        for ($i = 0; $i < self::PAST_BOOKINGS; $i++) {
            $plans[] = $this->planPastBooking($vehicles, $drivers, $chains);
        }

        foreach (self::UPCOMING_BOOKINGS as [$status, $waitingLevel]) {
            $plans[] = $this->planUpcomingBooking($vehicles, $drivers, $chains, $status, $waitingLevel);
        }

        $plans = array_values(array_filter($plans));
        $plans = $this->assignOdometers($plans);

        usort($plans, fn (array $a, array $b) => $a['created_at'] <=> $b['created_at']);

        DB::transaction(function () use ($plans): void {
            foreach ($plans as $plan) {
                $this->createBooking($plan);
            }
        });
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     * @param  Collection<int, Driver>  $drivers
     * @param  array<string, list<User>>  $chains
     * @return Plan|null
     */
    private function planPastBooking(Collection $vehicles, Collection $drivers, array $chains): ?array
    {
        $roll = fake()->numberBetween(1, 100);
        $status = match (true) {
            $roll <= 75 => BookingStatus::Completed,
            $roll <= 87 => BookingStatus::Rejected,
            default => BookingStatus::Cancelled,
        };

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $vehicle = $this->pickVehicle($vehicles, availableOnly: false);
            $driver = $this->pickDriver($drivers, $vehicle, activeOnly: false);
            [$startAt, $endAt] = $this->schedule($this->now->subDays(fake()->numberBetween(4, 180)));

            if (! $this->isFree($vehicle, $driver, $startAt, $endAt)) {
                continue;
            }

            $this->reserve($vehicle, $driver, $startAt, $endAt);

            $approvers = $this->pickChain($vehicle, $chains);
            $totalLevels = count($approvers);
            $cancelledAfterApproval = $status === BookingStatus::Cancelled && fake()->boolean();

            $currentLevel = match (true) {
                $status === BookingStatus::Completed, $cancelledAfterApproval => $totalLevels,
                $status === BookingStatus::Rejected => fake()->boolean(60) ? 1 : fake()->numberBetween(2, $totalLevels),
                default => fake()->numberBetween(1, $totalLevels),
            };

            return $this->makePlan($vehicle, $driver, $approvers, $status, $currentLevel, $startAt, $endAt, [
                'cancelled_after_approval' => $cancelledAfterApproval,
                'created_at' => $startAt->subDays(fake()->numberBetween(1, 6))->setTime(fake()->numberBetween(8, 15), fake()->numberBetween(0, 59)),
                'distance' => $status === BookingStatus::Completed ? $this->distanceFor($startAt, $endAt) : null,
            ]);
        }

        return null;
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     * @param  Collection<int, Driver>  $drivers
     * @param  array<string, list<User>>  $chains
     * @return Plan|null
     */
    private function planUpcomingBooking(
        Collection $vehicles,
        Collection $drivers,
        array $chains,
        BookingStatus $status,
        ?int $waitingLevel,
    ): ?array {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $vehicle = $this->pickVehicle($vehicles, availableOnly: true);
            $driver = $this->pickDriver($drivers, $vehicle, activeOnly: true);
            [$startAt, $endAt] = $this->schedule($this->now->addDays(fake()->numberBetween(1, 14)));

            if (! $this->isFree($vehicle, $driver, $startAt, $endAt)) {
                continue;
            }

            $this->reserve($vehicle, $driver, $startAt, $endAt);

            $approvers = $this->pickChain($vehicle, $chains);

            return $this->makePlan($vehicle, $driver, $approvers, $status, $waitingLevel ?? count($approvers), $startAt, $endAt, [
                'created_at' => $this->now->subMinutes(fake()->numberBetween(90, 72 * 60)),
            ]);
        }

        return null;
    }

    /**
     * @param  list<User>  $approvers
     * @param  array{cancelled_after_approval?: bool, created_at: CarbonImmutable, distance?: int|null}  $extra
     * @return Plan
     */
    private function makePlan(
        Vehicle $vehicle,
        Driver $driver,
        array $approvers,
        BookingStatus $status,
        int $currentLevel,
        CarbonImmutable $startAt,
        CarbonImmutable $endAt,
        array $extra,
    ): array {
        return [
            'vehicle' => $vehicle,
            'driver' => $driver,
            'approvers' => $approvers,
            'status' => $status,
            'current_level' => $currentLevel,
            'cancelled_after_approval' => $extra['cancelled_after_approval'] ?? false,
            'requester' => fake()->randomElement(self::REQUESTERS),
            'purpose' => fake()->randomElement(self::PURPOSES[$vehicle->type->value]),
            'destination' => fake()->randomElement(self::DESTINATIONS[$this->regionNames[$vehicle->location->region_id]]),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'created_at' => $extra['created_at'],
            'distance' => $extra['distance'] ?? null,
            'odometer_start' => null,
        ];
    }

    /**
     * Jam berangkat 06.00–14.30; sebagian besar perjalanan sehari, sisanya 1–2 hari.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function schedule(CarbonImmutable $day): array
    {
        $startAt = $day->setTime(fake()->numberBetween(6, 14), fake()->randomElement([0, 30]));

        $hours = fake()->boolean(70)
            ? fake()->numberBetween(3, 10)
            : fake()->numberBetween(1, 2) * 24 + fake()->numberBetween(0, 6);

        return [$startAt, $startAt->addHours($hours)];
    }

    private function distanceFor(CarbonImmutable $startAt, CarbonImmutable $endAt): int
    {
        return $startAt->diffInHours($endAt) < 24
            ? fake()->numberBetween(50, 220)
            : fake()->numberBetween(150, 400);
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     */
    private function pickVehicle(Collection $vehicles, bool $availableOnly): Vehicle
    {
        $candidates = $availableOnly
            ? $vehicles->where('status', VehicleStatus::Available)
            : $vehicles;

        $pool = [];

        foreach ($candidates as $vehicle) {
            array_push($pool, ...array_fill(0, $this->vehicleWeights[$vehicle->id], $vehicle));
        }

        return fake()->randomElement($pool);
    }

    /**
     * Driver dipilih dari region yang sama dengan pool kendaraan.
     *
     * @param  Collection<int, Driver>  $drivers
     */
    private function pickDriver(Collection $drivers, Vehicle $vehicle, bool $activeOnly): Driver
    {
        $candidates = $drivers
            ->when($activeOnly, fn (Collection $drivers) => $drivers->where('is_active', true))
            ->filter(fn (Driver $driver) => $driver->location->region_id === $vehicle->location->region_id);

        return $candidates->isNotEmpty()
            ? $candidates->random()
            : $drivers->where('is_active', true)->random();
    }

    /**
     * @param  array<string, list<User>>  $chains
     * @return list<User>
     */
    private function pickChain(Vehicle $vehicle, array $chains): array
    {
        if (fake()->boolean(25)) {
            return $chains['escalated'];
        }

        return $this->regionNames[$vehicle->location->region_id] === 'Sulawesi Tenggara'
            ? $chains['default']
            : $chains['branch'];
    }

    private function isFree(Vehicle $vehicle, Driver $driver, CarbonImmutable $startAt, CarbonImmutable $endAt): bool
    {
        foreach (["vehicle.{$vehicle->id}", "driver.{$driver->id}"] as $key) {
            foreach ($this->schedules[$key] ?? [] as [$takenStart, $takenEnd]) {
                if ($takenStart < $endAt && $takenEnd > $startAt) {
                    return false;
                }
            }
        }

        return true;
    }

    private function reserve(Vehicle $vehicle, Driver $driver, CarbonImmutable $startAt, CarbonImmutable $endAt): void
    {
        $this->schedules["vehicle.{$vehicle->id}"][] = [$startAt, $endAt];
        $this->schedules["driver.{$driver->id}"][] = [$startAt, $endAt];
    }

    /**
     * Odometer dihitung mundur dari odometer kendaraan saat ini sehingga
     * odometer_end booking terakhir sama dengan odometer kendaraan.
     *
     * @param  list<Plan>  $plans
     * @return list<Plan>
     */
    private function assignOdometers(array $plans): array
    {
        $completed = array_filter($plans, fn (array $plan) => $plan['status'] === BookingStatus::Completed);

        uasort($completed, fn (array $a, array $b) => $b['start_at'] <=> $a['start_at']);

        $odometers = [];
        $odometerStarts = [];

        foreach ($completed as $index => $plan) {
            $vehicleId = $plan['vehicle']->id;
            $odometerEnd = $odometers[$vehicleId] ?? $plan['vehicle']->odometer;

            $odometerStarts[$index] = $odometers[$vehicleId] = $odometerEnd - (int) $plan['distance'];
        }

        return array_map(
            fn (array $plan, int $index) => [...$plan, 'odometer_start' => $odometerStarts[$index] ?? null],
            $plans,
            array_keys($plans),
        );
    }

    /**
     * @param  Plan  $plan
     */
    private function createBooking(array $plan): void
    {
        $status = $plan['status'];
        $approvers = $plan['approvers'];
        $totalLevels = count($approvers);
        $currentLevel = $plan['current_level'];

        $decidedLevels = match (true) {
            $status === BookingStatus::Approved, $status === BookingStatus::Completed => $totalLevels,
            $status === BookingStatus::Rejected => $currentLevel,
            $plan['cancelled_after_approval'] => $totalLevels,
            default => $currentLevel - 1,
        };

        $isUpcoming = $plan['start_at'] > $this->now;
        $deadline = $isUpcoming ? $this->now : $plan['start_at'];
        $actions = $decidedLevels + ($status === BookingStatus::Cancelled ? 1 : 0);
        $times = $this->spreadTimes($plan['created_at'], $deadline->min($plan['created_at']->addDay()), $actions);

        $lastActionAt = $times === [] ? $plan['created_at'] : $times[array_key_last($times)];
        $completedAt = $status === BookingStatus::Completed
            ? $plan['end_at']->addMinutes(fake()->numberBetween(30, 240))
            : null;
        $cancelledAt = $status === BookingStatus::Cancelled ? $lastActionAt : null;
        $fuelUsed = $status === BookingStatus::Completed ? $this->fuelFor($plan['vehicle'], (int) $plan['distance']) : null;

        $booking = Booking::factory()->create([
            'requester_name' => $plan['requester'][0],
            'requester_department' => $plan['requester'][1],
            'purpose' => $plan['purpose'],
            'destination' => $plan['destination'],
            'vehicle_id' => $plan['vehicle']->id,
            'driver_id' => $plan['driver']->id,
            'created_by' => $this->admin->id,
            'start_at' => $plan['start_at'],
            'end_at' => $plan['end_at'],
            'status' => $status,
            'current_level' => $currentLevel,
            'odometer_start' => $plan['odometer_start'],
            'odometer_end' => $plan['odometer_start'] === null ? null : $plan['odometer_start'] + (int) $plan['distance'],
            'fuel_used' => $fuelUsed,
            'completed_at' => $completedAt,
            'cancel_reason' => $status === BookingStatus::Cancelled ? fake()->randomElement(self::CANCEL_REASONS) : null,
            'cancelled_at' => $cancelledAt,
            'created_at' => $plan['created_at'],
            'updated_at' => $completedAt ?? $lastActionAt,
        ]);

        $this->log($this->admin, 'booking.created', $booking, $plan['created_at'],
            "{$this->admin->name} membuat pemesanan {$booking->code} untuk {$booking->requester_name} ({$plan['vehicle']->plate_number})",
            [
                'vehicle' => $plan['vehicle']->plate_number,
                'driver' => $plan['driver']->name,
                'start_at' => $booking->start_at->toDateTimeString(),
                'end_at' => $booking->end_at->toDateTimeString(),
                'approvers' => array_map(fn (User $approver) => $approver->name, $approvers),
            ],
        );

        foreach ($approvers as $index => $approver) {
            $level = $index + 1;
            $approvalStatus = match (true) {
                $level < $decidedLevels || ($level === $decidedLevels && $status !== BookingStatus::Rejected) => ApprovalStatus::Approved,
                $level === $decidedLevels => ApprovalStatus::Rejected,
                $status === BookingStatus::Pending => ApprovalStatus::Pending,
                default => ApprovalStatus::Skipped,
            };
            $actedAt = in_array($approvalStatus, [ApprovalStatus::Approved, ApprovalStatus::Rejected], true) ? $times[$index] : null;
            $note = match (true) {
                $approvalStatus === ApprovalStatus::Rejected => fake()->randomElement(self::REJECT_NOTES),
                $approvalStatus === ApprovalStatus::Approved && fake()->boolean(30) => fake()->randomElement(self::APPROVE_NOTES),
                default => null,
            };

            $approval = BookingApproval::factory()->for($booking)->for($approver, 'approver')->create([
                'level' => $level,
                'status' => $approvalStatus,
                'note' => $note,
                'acted_at' => $actedAt,
                'created_at' => $plan['created_at'],
                'updated_at' => $actedAt ?? $cancelledAt ?? $plan['created_at'],
            ]);

            if ($actedAt === null) {
                continue;
            }

            $verb = $approvalStatus === ApprovalStatus::Approved ? 'menyetujui' : 'menolak';

            $this->log($approver, "approval.{$approvalStatus->value}", $booking, $actedAt,
                "{$approver->name} {$verb} {$booking->code} (level {$level} dari {$totalLevels})",
                ['approval_id' => $approval->id, 'level' => $level, 'total_levels' => $totalLevels, 'note' => $note],
            );
        }

        if ($cancelledAt !== null) {
            $this->log($this->admin, 'booking.cancelled', $booking, $cancelledAt,
                "{$this->admin->name} membatalkan {$booking->code}",
                ['reason' => $booking->cancel_reason],
            );
        }

        if ($completedAt !== null) {
            $this->log($this->admin, 'booking.completed', $booking, $completedAt,
                sprintf('%s menyelesaikan %s (%d km, %s L)', $this->admin->name, $booking->code, $plan['distance'], number_format((float) $fuelUsed, 2, ',', '.')),
                [
                    'odometer_start' => $booking->odometer_start,
                    'odometer_end' => $booking->odometer_end,
                    'distance' => $plan['distance'],
                    'fuel_used' => $fuelUsed,
                ],
            );
        }
    }

    /**
     * Waktu aksi yang berurutan di antara dua titik waktu.
     *
     * @return list<CarbonImmutable>
     */
    private function spreadTimes(CarbonImmutable $from, CarbonImmutable $until, int $count): array
    {
        $seconds = max($until->getTimestamp() - $from->getTimestamp(), $count * 60);
        $times = [];

        for ($i = 0; $i < $count; $i++) {
            $fraction = ($i + fake()->randomFloat(2, 0.2, 0.9)) / $count;
            $times[] = $from->addSeconds((int) round($seconds * $fraction));
        }

        return $times;
    }

    /**
     * Konsumsi BBM: angkutan orang 8,5–11,5 km/L, angkutan barang 3,5–5,5 km/L.
     */
    private function fuelFor(Vehicle $vehicle, int $distance): float
    {
        $kilometersPerLiter = $vehicle->type === VehicleType::Passenger
            ? fake()->randomFloat(1, 8.5, 11.5)
            : fake()->randomFloat(1, 3.5, 5.5);

        return round($distance / $kilometersPerLiter, 1);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(User $user, string $action, Booking $booking, CarbonImmutable $at, string $description, array $properties): void
    {
        ActivityLog::factory()->for($user)->for($booking, 'subject')->create([
            'action' => $action,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => '10.20.1.'.(10 + $user->id),
            'user_agent' => self::USER_AGENT,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
