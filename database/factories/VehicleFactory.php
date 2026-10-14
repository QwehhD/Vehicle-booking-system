<?php

namespace Database\Factories;

use App\Enums\VehicleOwnership;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Location;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lastServiceAt = fake()->dateTimeBetween('-90 days', '-10 days');

        return [
            'plate_number' => fake()->unique()->bothify('DT #### ??'),
            'name' => fake()->randomElement(['Toyota Hilux', 'Toyota Innova Zenix', 'Mitsubishi Triton']),
            'type' => VehicleType::Passenger,
            'ownership' => VehicleOwnership::Owned,
            'rental_company' => null,
            'location_id' => Location::factory(),
            'odometer' => fake()->numberBetween(10_000, 150_000),
            'status' => VehicleStatus::Available,
            'last_service_at' => $lastServiceAt,
            'next_service_at' => (clone $lastServiceAt)->modify('+120 days'),
        ];
    }

    public function cargo(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement(['Hino Dutro', 'Mitsubishi Fuso Canter', 'Hino 500 Dump Truck']),
            'type' => VehicleType::Cargo,
        ]);
    }

    public function rented(): static
    {
        return $this->state(fn (array $attributes) => [
            'ownership' => VehicleOwnership::Rented,
            'rental_company' => 'PT '.fake()->lastName().' Rental',
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::Maintenance,
        ]);
    }
}
