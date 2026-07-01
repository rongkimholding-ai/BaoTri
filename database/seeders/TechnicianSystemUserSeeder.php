<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TechnicianSystemUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate([
            'name' => 'technician_system',
            'guard_name' => 'web',
        ]);

        $technicians = config('technician_ht', []);

        foreach ($technicians as $tech) {

            if (
                !is_array($tech)
                || empty($tech['email'])
                || empty($tech['name'])
            ) {
                continue;
            }

            $user = User::updateOrCreate(
                [
                    'email' => strtolower(trim($tech['email']))
                ],
                [
                    'name' => trim($tech['name']),
                    'password' => Hash::make('12345678'),
                ]
            );

            $user->syncRoles('technician_system');
        }
    }
}