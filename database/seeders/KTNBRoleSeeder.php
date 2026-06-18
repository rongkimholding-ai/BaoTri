<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
class KTNBRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin',
            'manager',
            'technician',
            'user',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        // Tạo user
        $users = [
            [
                'email' => 'ktnb1@tocotocotea.com',
                'name' => 'KTNB 1'
            ],
            [
                'email' => 'ktnb2@tocotocotea.com',
                'name' => 'KTNB 2'
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                [
                    'email' => $userData['email'],
                ],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('12345678'),
                ]
            );

            // Gán role
            $user->assignRole('manager');
        }

    }
}