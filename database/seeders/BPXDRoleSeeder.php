<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
class BPXDRoleSeeder extends Seeder
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
        $technicians = config('technician');
        $users = [];

        foreach ($technicians as $tech) {
            // Bỏ qua các khóa không phải là mảng (ví dụ 'ngoai_gio')
            if (!is_array($tech) || !isset($tech['email']) || !isset($tech['name'])) {
                continue;
            }
            $users[] = [
                'email' => $tech['email'],
                'name' => $tech['name'],
            ];
        }


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

            // Gán role theo email
            if ($userData['email'] === 'liemhoang.support.hcm@tocototea.com') {
                $user->assignRole('manager');
            } elseif ($userData['email'] === 'dunguyen.support@tocotocotea.com') {
                $user->assignRole('manager');
            } else {
                $user->assignRole('technician');
            }
        }


    }
}