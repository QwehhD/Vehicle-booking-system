<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Location;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * @var list<array{name: string, phone: string, license_number: string, location: string, is_active: bool}>
     */
    private const DRIVERS = [
        ['name' => 'Rahmat Hidayat', 'phone' => '081241556780', 'license_number' => '740112345678', 'location' => 'Kantor Pusat Kendari', 'is_active' => true],
        ['name' => 'La Ode Haris', 'phone' => '085241778901', 'license_number' => '740198765432', 'location' => 'Tambang Pomalaa', 'is_active' => true],
        ['name' => 'Agus Salim', 'phone' => '082293445612', 'license_number' => '740155667788', 'location' => 'Tambang Pomalaa', 'is_active' => true],
        ['name' => 'Muhammad Rizal', 'phone' => '081355210934', 'license_number' => '740133224455', 'location' => 'Tambang Kolaka', 'is_active' => false],
        ['name' => 'Hendra Lamba', 'phone' => '085342667120', 'license_number' => '740177889900', 'location' => 'Tambang Konawe Utara', 'is_active' => true],
        ['name' => 'Wayan Sudarta', 'phone' => '081243908812', 'license_number' => '720112093847', 'location' => 'Kantor Cabang Morowali', 'is_active' => true],
        ['name' => 'Yusuf Tandi', 'phone' => '082190334578', 'license_number' => '720145678123', 'location' => 'Tambang Bahodopi', 'is_active' => true],
        ['name' => 'Darmawan Saputra', 'phone' => '085256001239', 'license_number' => '720198123456', 'location' => 'Tambang Morowali Utara', 'is_active' => true],
        ['name' => 'Ismail Tuasikal', 'phone' => '082187650012', 'license_number' => '820134567890', 'location' => 'Tambang Halmahera Timur', 'is_active' => true],
        ['name' => 'Arifin Lasaeba', 'phone' => '081340098765', 'license_number' => '820176543210', 'location' => 'Tambang Halmahera Timur', 'is_active' => true],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = Location::pluck('id', 'name');

        foreach (self::DRIVERS as $driver) {
            Driver::factory()->create([
                'name' => $driver['name'],
                'phone' => $driver['phone'],
                'license_number' => $driver['license_number'],
                'location_id' => $locations[$driver['location']],
                'is_active' => $driver['is_active'],
            ]);
        }
    }
}
