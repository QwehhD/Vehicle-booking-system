<?php

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region_id' => Region::factory(),
            'name' => 'Tambang '.fake()->city(),
            'type' => LocationType::Mine,
            'address' => fake()->streetAddress(),
        ];
    }

    public function headOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Kantor Pusat '.fake()->city(),
            'type' => LocationType::HeadOffice,
        ]);
    }

    public function branchOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Kantor Cabang '.fake()->city(),
            'type' => LocationType::BranchOffice,
        ]);
    }
}
