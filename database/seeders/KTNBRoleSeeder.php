<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
class KTNBRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Danh sách permission
        $permissions = [
            'view data',
            'create data',
            'export excel',
            'export excel tech',
            'delete data',
            'confirm maintenance',
            'remind maintenance',
            'change-maintenance-status',
        ];

        $role = Role::firstOrCreate([
            'name' => 'ktnb',
            'guard_name' => 'web',
        ]);

        // Gán quyền cho role
        $role->syncPermissions($permissions);

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
            $user->assignRole($role);
        }

    }
}