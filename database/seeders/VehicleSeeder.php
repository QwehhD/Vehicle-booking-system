<?php

namespace Database\Seeders;

use App\Enums\VehicleOwnership;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Location;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * `service_in` adalah jumlah hari menuju jadwal service berikutnya.
     *
     * @var list<array{plate: string, name: string, type: VehicleType, rental: string|null, location: string, odometer: int, maintenance: bool, service_in: int}>
     */
    private const VEHICLES = [
        ['plate' => 'DT 1234 AB', 'name' => 'Toyota Hilux Double Cabin', 'type' => VehicleType::Passenger, 'rental' => null, 'location' => 'Tambang Pomalaa', 'odometer' => 84_120, 'maintenance' => false, 'service_in' => 4],
        ['plate' => 'DT 1456 KD', 'name' => 'Toyota Innova Zenix', 'type' => VehicleType::Passenger, 'rental' => null, 'location' => 'Kantor Pusat Kendari', 'odometer' => 23_480, 'maintenance' => false, 'service_in' => 58],
        ['plate' => 'DT 7789 KL', 'name' => 'Toyota Hiace Commuter', 'type' => VehicleType::Passenger, 'rental' => 'PT Sultra Rental Mandiri', 'location' => 'Tambang Kolaka', 'odometer' => 61_905, 'maintenance' => false, 'service_in' => 33],
        ['plate' => 'DT 8123 KU', 'name' => 'Mitsubishi Triton', 'type' => VehicleType::Passenger, 'rental' => null, 'location' => 'Tambang Konawe Utara', 'odometer' => 97_350, 'maintenance' => false, 'service_in' => 41],
        ['plate' => 'DT 9012 PM', 'name' => 'Isuzu Elf NLR', 'type' => VehicleType::Passenger, 'rental' => 'PT Sultra Rental Mandiri', 'location' => 'Tambang Pomalaa', 'odometer' => 132_760, 'maintenance' => false, 'service_in' => 27],
        ['plate' => 'DT 8456 AA', 'name' => 'Hino Dutro 130 HD', 'type' => VehicleType::Cargo, 'rental' => null, 'location' => 'Tambang Pomalaa', 'odometer' => 118_430, 'maintenance' => false, 'service_in' => 9],
        ['plate' => 'DT 8890 KL', 'name' => 'Mitsubishi Fuso Canter', 'type' => VehicleType::Cargo, 'rental' => 'CV Kolaka Jaya Transport', 'location' => 'Tambang Kolaka', 'odometer' => 76_215, 'maintenance' => false, 'service_in' => 66],
        ['plate' => 'DT 9345 KU', 'name' => 'Hino 500 Dump Truck', 'type' => VehicleType::Cargo, 'rental' => null, 'location' => 'Tambang Konawe Utara', 'odometer' => 154_870, 'maintenance' => true, 'service_in' => 45],
        ['plate' => 'DN 1122 MR', 'name' => 'Toyota Hilux Double Cabin', 'type' => VehicleType::Passenger, 'rental' => null, 'location' => 'Kantor Cabang Morowali', 'odometer' => 45_690, 'maintenance' => false, 'service_in' => 52],
        ['plate' => 'DN 3344 BH', 'name' => 'Mitsubishi Triton', 'type' => VehicleType::Passenger, 'rental' => null, 'location' => 'Tambang Bahodopi', 'odometer' => 102_330, 'maintenance' => false, 'service_in' => 12],
        ['plate' => 'DN 5566 BH', 'name' => 'Hino 500 Dump Truck', 'type' => VehicleType::Cargo, 'rental' => null, 'location' => 'Tambang Bahodopi', 'odometer' => 139_080, 'maintenance' => false, 'service_in' => 38],
        ['plate' => 'DN 7788 MU', 'name' => 'Toyota Hiace Commuter', 'type' => VehicleType::Passenger, 'rental' => 'CV Morowali Trans', 'location' => 'Tambang Morowali Utara', 'odometer' => 58_410, 'maintenance' => false, 'service_in' => 71],
        ['plate' => 'DN 9900 MU', 'name' => 'Mitsubishi Fuso Canter', 'type' => VehicleType::Cargo, 'rental' => null, 'location' => 'Tambang Morowali Utara', 'odometer' => 88_760, 'maintenance' => true, 'service_in' => 75],
        ['plate' => 'DG 1357 HT', 'name' => 'Toyota Hilux Double Cabin', 'type' => VehicleType::Passenger, 'rental' => 'PT Halmahera Armada', 'location' => 'Tambang Halmahera Timur', 'odometer' => 39_870, 'maintenance' => false, 'service_in' => 47],
        ['plate' => 'DG 2468 HT', 'name' => 'Hino 500 Dump Truck', 'type' => VehicleType::Cargo, 'rental' => null, 'location' => 'Tambang Halmahera Timur', 'odometer' => 121_540, 'maintenance' => false, 'service_in' => 29],
        ['plate' => 'DG 3579 HT', 'name' => 'Toyota Innova Zenix', 'type' => VehicleType::Passenger, 'rental' => null, 'location' => 'Tambang Halmahera Timur', 'odometer' => 18_920, 'maintenance' => false, 'service_in' => 63],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = Location::pluck('id', 'name');
        $today = today();

        foreach (self::VEHICLES as $vehicle) {
            $nextServiceAt = $today->addDays($vehicle['service_in']);

            Vehicle::factory()->create([
                'plate_number' => $vehicle['plate'],
                'name' => $vehicle['name'],
                'type' => $vehicle['type'],
                'ownership' => $vehicle['rental'] === null ? VehicleOwnership::Owned : VehicleOwnership::Rented,
                'rental_company' => $vehicle['rental'],
                'location_id' => $locations[$vehicle['location']],
                'odometer' => $vehicle['odometer'],
                'status' => $vehicle['maintenance'] ? VehicleStatus::Maintenance : VehicleStatus::Available,
                'last_service_at' => $nextServiceAt->subMonths(4),
                'next_service_at' => $nextServiceAt,
            ]);
        }
    }
}
