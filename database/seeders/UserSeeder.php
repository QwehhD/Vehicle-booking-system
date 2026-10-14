<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Akun demo; password semuanya "password".
     *
     * @var list<array{email: string, name: string, role: UserRole, position: string, location: string}>
     */
    private const USERS = [
        ['email' => 'admin@nikel.test', 'name' => 'Admin Pool', 'role' => UserRole::Admin, 'position' => 'Staf Administrasi Pool', 'location' => 'Kantor Pusat Kendari'],
        ['email' => 'approver1@nikel.test', 'name' => 'Budi Santoso', 'role' => UserRole::Approver, 'position' => 'Supervisor Pool', 'location' => 'Kantor Pusat Kendari'],
        ['email' => 'approver2@nikel.test', 'name' => 'Siti Rahmawati', 'role' => UserRole::Approver, 'position' => 'Manager Operasional', 'location' => 'Kantor Pusat Kendari'],
        ['email' => 'approver3@nikel.test', 'name' => 'Andi Pratama', 'role' => UserRole::Approver, 'position' => 'Kepala Cabang', 'location' => 'Kantor Cabang Morowali'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = Location::pluck('id', 'name');

        foreach (self::USERS as $user) {
            User::factory()->create([
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'position' => $user['position'],
                'location_id' => $locations[$user['location']],
            ]);
        }
    }
}
