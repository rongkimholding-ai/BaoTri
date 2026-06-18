<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
class BPXDRoleSeeder extends Seeder
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
            'name' => 'BPXD',
            'guard_name' => 'web',
        ]);

        $roleTN = Role::firstOrCreate([
            'name' => 'BPXD TN',
            'guard_name' => 'web',
        ]);

        $roleManager = Role::firstOrCreate([
            'name' => 'BPXD manager',
            'guard_name' => 'web',
        ]);
   

        // Gán quyền cho role
        $roleManager->syncPermissions($permissions);
        $roleTN->syncPermissions([
            'view data',
            'create data',
            'export excel',
            'export excel tech',
            'change-maintenance-status',
        ]);
        $role->syncPermissions([
            'view data',
            'change-maintenance-status',
        ]);
   

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
                $user->assignRole($roleManager);
            } elseif ($userData['email'] === 'dunguyen.support@tocotocotea.com') {
                $user->assignRole($roleTN);
            } else {
                $user->assignRole($role);
            }
        }


    }
}