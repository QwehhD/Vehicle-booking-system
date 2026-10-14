<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * Pilihan kendaraan, driver, dan approver untuk form pemesanan.
     *
     * @return array{
     *     vehicles: list<array{value: int, label: string}>,
     *     drivers: list<array{value: int, label: string}>,
     *     approvers: list<array{value: int, label: string, description: string|null}>,
     * }
     */
    public function formOptions(): array
    {
        $vehicles = Vehicle::query()
            ->with('location')
            ->where('status', '!=', VehicleStatus::Maintenance)
            ->orderBy('plate_number')
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'value' => $vehicle->id,
                'label' => sprintf(
                    '%s — %s · %s · %s · %s',
                    $vehicle->plate_number,
                    $vehicle->name,
                    $vehicle->type->label(),
                    $vehicle->ownership->label(),
                    $vehicle->location->name,
                ),
            ]);

        $drivers = Driver::query()
            ->with('location')
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (Driver $driver) => [
                'value' => $driver->id,
                'label' => "{$driver->name} — {$driver->location->name}",
            ]);

        $approvers = User::query()
            ->where('role', UserRole::Approver)
            ->orderBy('name')
            ->get()
            ->map(fn (User $approver) => [
                'value' => $approver->id,
                'label' => $approver->name,
                'description' => $approver->position,
            ]);

        return [
            'vehicles' => array_values($vehicles->all()),
            'drivers' => array_values($drivers->all()),
            'approvers' => array_values($approvers->all()),
        ];
    }

    /**
     * Membuat pemesanan berstatus pending beserta approval berjenjang
     * sesuai urutan approver (indeks 0 = level 1).
     *
     * @param  array{requester_name: string, requester_department: string, purpose: string, destination: string, start_at: string, end_at: string, vehicle_id: int, driver_id: int, approvers: list<int>}  $data
     *
     * @throws ValidationException
     */
    public function create(User $admin, array $data): Booking
    {
        return DB::transaction(function () use ($admin, $data): Booking {
            $startAt = CarbonImmutable::parse($data['start_at']);
            $endAt = CarbonImmutable::parse($data['end_at']);

            // Mengunci kendaraan dan driver agar dua pemesanan bersamaan tidak lolos cek bentrok.
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($data['vehicle_id']);
            $driver = Driver::query()->lockForUpdate()->findOrFail($data['driver_id']);

            $this->ensureScheduleIsFree($vehicle, $driver, $startAt, $endAt);

            $booking = Booking::create([
                'code' => Booking::temporaryCode(),
                'requester_name' => $data['requester_name'],
                'requester_department' => $data['requester_department'],
                'purpose' => $data['purpose'],
                'destination' => $data['destination'],
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'created_by' => $admin->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => BookingStatus::Pending,
                'current_level' => 1,
            ]);
            $booking->assignCode();

            $approverIds = $data['approvers'];
            $approverNames = User::query()->whereKey($approverIds)->pluck('name', 'id');

            foreach ($approverIds as $index => $approverId) {
                $booking->approvals()->create([
                    'approver_id' => $approverId,
                    'level' => $index + 1,
                    'status' => ApprovalStatus::Pending,
                ]);
            }

            $this->activityLogger->log(
                'booking.created',
                $booking,
                "{$admin->name} membuat pemesanan {$booking->code} untuk {$booking->requester_name} ({$vehicle->plate_number})",
                [
                    'vehicle' => $vehicle->plate_number,
                    'driver' => $driver->name,
                    'start_at' => $startAt->toDateTimeString(),
                    'end_at' => $endAt->toDateTimeString(),
                    'approvers' => array_map(fn (int $id) => $approverNames[$id], $approverIds),
                ],
            );

            return $booking;
        });
    }

    /**
     * Membatalkan pemesanan pending/approved; approval yang masih pending dilewati.
     */
    public function cancel(User $admin, Booking $booking, string $reason): Booking
    {
        return DB::transaction(function () use ($admin, $booking, $reason): Booking {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            Gate::forUser($admin)->authorize('cancel', $booking);

            $previousStatus = $booking->status;

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancel_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            BookingApproval::query()
                ->where('booking_id', $booking->id)
                ->where('status', ApprovalStatus::Pending)
                ->update(['status' => ApprovalStatus::Skipped]);

            $this->activityLogger->log(
                'booking.cancelled',
                $booking,
                "{$admin->name} membatalkan {$booking->code}",
                ['reason' => $reason, 'previous_status' => $previousStatus->value],
            );

            return $booking;
        });
    }

    /**
     * Menyelesaikan pemesanan approved dan memperbarui odometer kendaraan.
     *
     * @param  array{odometer_start: int, odometer_end: int, fuel_used: float}  $data
     */
    public function complete(User $admin, Booking $booking, array $data): Booking
    {
        return DB::transaction(function () use ($admin, $booking, $data): Booking {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            Gate::forUser($admin)->authorize('complete', $booking);

            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($booking->vehicle_id);
            $odometerStart = $data['odometer_start'];
            $odometerEnd = $data['odometer_end'];
            $fuelUsed = round($data['fuel_used'], 2);

            $booking->update([
                'status' => BookingStatus::Completed,
                'odometer_start' => $odometerStart,
                'odometer_end' => $odometerEnd,
                'fuel_used' => $fuelUsed,
                'completed_at' => now(),
            ]);

            $vehicle->update(['odometer' => $odometerEnd]);

            $distance = $odometerEnd - $odometerStart;

            $this->activityLogger->log(
                'booking.completed',
                $booking,
                sprintf(
                    '%s menyelesaikan %s (%d km, %s L)',
                    $admin->name,
                    $booking->code,
                    $distance,
                    number_format($fuelUsed, 2, ',', '.'),
                ),
                [
                    'odometer_start' => $odometerStart,
                    'odometer_end' => $odometerEnd,
                    'distance' => $distance,
                    'fuel_used' => $fuelUsed,
                ],
            );

            return $booking;
        });
    }

    /**
     * Menolak jadwal yang beririsan dengan pemesanan pending/approved lain
     * untuk kendaraan atau driver yang sama.
     *
     * @throws ValidationException
     */
    private function ensureScheduleIsFree(Vehicle $vehicle, Driver $driver, CarbonImmutable $startAt, CarbonImmutable $endAt): void
    {
        $conflictFor = fn (string $column, int $id): ?Booking => Booking::query()
            ->active()
            ->overlapping($startAt, $endAt)
            ->where($column, $id)
            ->orderBy('start_at')
            ->first();

        $errors = [];

        if ($conflict = $conflictFor('vehicle_id', $vehicle->id)) {
            $errors['vehicle_id'] = "Kendaraan {$vehicle->plate_number} sudah dipesan untuk {$conflict->code} ({$this->schedule($conflict)}).";
        }

        if ($conflict = $conflictFor('driver_id', $driver->id)) {
            $errors['driver_id'] = "Driver {$driver->name} sudah bertugas untuk {$conflict->code} ({$this->schedule($conflict)}).";
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function schedule(Booking $booking): string
    {
        return $booking->start_at->translatedFormat('j M Y, H:i').' – '.$booking->end_at->translatedFormat('j M Y, H:i');
    }
}
