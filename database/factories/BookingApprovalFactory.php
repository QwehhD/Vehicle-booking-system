<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingApproval>
 */
class BookingApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'approver_id' => User::factory()->approver(),
            'level' => 1,
            'status' => ApprovalStatus::Pending,
            'note' => null,
            'acted_at' => null,
        ];
    }
}
