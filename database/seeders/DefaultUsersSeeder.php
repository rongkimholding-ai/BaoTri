<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo role nếu chưa có
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

        // Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@tea.com'],
            [
                'name' => 'Admin BaoTri',
                'password' => Hash::make('abcd@1234'),
            ]
        );

        $admin->syncRoles(['admin']);

        // Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@tea.com'],
            [
                'name' => 'Manager',
                'password' => Hash::make('abcd@1234'),
            ]
        );

        $manager->syncRoles(['manager']);

        // Technician
        $technician = User::updateOrCreate(
            ['email' => 'technician@tea.com'],
            [
                'name' => 'Technician',
                'password' => Hash::make('abcd@1234'),
            ]
        );
        // Thêm các kỹ thuật viên từ config/technician.php với vai trò technician
        $technicians = config('technician');
        foreach ($technicians as $tech) {
            $user = User::updateOrCreate(
                ['email' => $tech['email']],
                [
                    'name' => $tech['name'],
                    'password' => Hash::make('12345678'),
                ]
            );
            $user->syncRoles(['technician']);
        }

        $technician->syncRoles(['technician']);

        // User
        $user = User::updateOrCreate(
            ['email' => 'user@tea.com'],
            [
                'name' => 'User',
                'password' => Hash::make('abcd@1234'),
            ]
        );

        $user->syncRoles(['user']);
    }
}