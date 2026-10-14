<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = CarbonImmutable::instance(fake()->dateTimeBetween('+1 day', '+14 days'))
            ->setTime(fake()->numberBetween(6, 14), 0);

        return [
            'code' => Booking::temporaryCode(),
            'requester_name' => fake()->name(),
            'requester_department' => fake()->randomElement(['Operasional Tambang', 'Eksplorasi', 'K3 & Lingkungan', 'Logistik']),
            'purpose' => fake()->sentence(),
            'destination' => fake()->city(),
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => Driver::factory(),
            'created_by' => User::factory()->admin(),
            'start_at' => $startAt,
            'end_at' => $startAt->addHours(fake()->numberBetween(2, 10)),
            'status' => BookingStatus::Pending,
            'current_level' => 1,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Booking $booking): void {
            if ($booking->hasTemporaryCode()) {
                $booking->assignCode();
            }
        });
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Approved,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Rejected,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
            'cancel_reason' => 'Kegiatan ditunda',
            'cancelled_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $odometerStart = fake()->numberBetween(10_000, 150_000);

            return [
                'status' => BookingStatus::Completed,
                'odometer_start' => $odometerStart,
                'odometer_end' => $odometerStart + fake()->numberBetween(50, 400),
                'fuel_used' => fake()->randomFloat(2, 5, 60),
                'completed_at' => now(),
            ];
        });
    }

    /**
     * Membuat approval berjenjang sesuai urutan approver (indeks 0 = level 1)
     * dengan status yang konsisten terhadap status dan current_level booking.
     */
    public function withApprovers(User ...$approvers): static
    {
        return $this->afterCreating(function (Booking $booking) use ($approvers): void {
            foreach (array_values($approvers) as $index => $approver) {
                $level = $index + 1;
                $status = $this->approvalStatusFor($booking, $level);

                BookingApproval::factory()
                    ->for($booking)
                    ->for($approver, 'approver')
                    ->create([
                        'level' => $level,
                        'status' => $status,
                        'note' => $status === ApprovalStatus::Rejected ? 'Jadwal kendaraan tidak sesuai kebutuhan.' : null,
                        'acted_at' => in_array($status, [ApprovalStatus::Approved, ApprovalStatus::Rejected], true) ? now() : null,
                    ]);
            }
        });
    }

    private function approvalStatusFor(Booking $booking, int $level): ApprovalStatus
    {
        return match ($booking->status) {
            BookingStatus::Approved, BookingStatus::Completed => ApprovalStatus::Approved,
            BookingStatus::Pending => $level < $booking->current_level ? ApprovalStatus::Approved : ApprovalStatus::Pending,
            BookingStatus::Rejected => match (true) {
                $level < $booking->current_level => ApprovalStatus::Approved,
                $level === $booking->current_level => ApprovalStatus::Rejected,
                default => ApprovalStatus::Skipped,
            },
            BookingStatus::Cancelled => $level < $booking->current_level ? ApprovalStatus::Approved : ApprovalStatus::Skipped,
        };
    }
}
