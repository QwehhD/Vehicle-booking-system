<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\Region;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Region beserta kantor dan tambang di dalamnya.
     *
     * @var array<string, list<array{name: string, type: LocationType, address: string}>>
     */
    private const REGIONS = [
        'Sulawesi Tenggara' => [
            ['name' => 'Kantor Pusat Kendari', 'type' => LocationType::HeadOffice, 'address' => 'Jl. Ahmad Yani No. 18, Kendari'],
            ['name' => 'Tambang Pomalaa', 'type' => LocationType::Mine, 'address' => 'Kec. Pomalaa, Kab. Kolaka'],
            ['name' => 'Tambang Kolaka', 'type' => LocationType::Mine, 'address' => 'Kec. Wolo, Kab. Kolaka'],
            ['name' => 'Tambang Konawe Utara', 'type' => LocationType::Mine, 'address' => 'Kec. Langgikima, Kab. Konawe Utara'],
        ],
        'Sulawesi Tengah' => [
            ['name' => 'Kantor Cabang Morowali', 'type' => LocationType::BranchOffice, 'address' => 'Jl. Trans Sulawesi, Bungku, Kab. Morowali'],
            ['name' => 'Tambang Bahodopi', 'type' => LocationType::Mine, 'address' => 'Kec. Bahodopi, Kab. Morowali'],
            ['name' => 'Tambang Morowali Utara', 'type' => LocationType::Mine, 'address' => 'Kec. Petasia Timur, Kab. Morowali Utara'],
        ],
        'Maluku Utara' => [
            ['name' => 'Tambang Halmahera Timur', 'type' => LocationType::Mine, 'address' => 'Kec. Maba, Kab. Halmahera Timur'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::REGIONS as $regionName => $locations) {
            $region = Region::factory()->create(['name' => $regionName]);

            foreach ($locations as $location) {
                Location::factory()->for($region)->create($location);
            }
        }
    }
}
