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
        /**
         * =========================
         * 1. CREATE ROLES
         * =========================
         */
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

        /**
         * =========================
         * 2. DEFAULT SYSTEM USERS
         * =========================
         */

        $systemUsers = [
            [
                'name' => 'Admin BaoTri',
                'email' => 'admin@tea.com',
                'password' => 'abcd@1234',
                'role' => 'admin',
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@tea.com',
                'password' => 'abcd@1234',
                'role' => 'manager',
            ],
            [
                'name' => 'Technician',
                'email' => 'technician@tea.com',
                'password' => 'abcd@1234',
                'role' => 'technician',
            ],
            [
                'name' => 'User',
                'email' => 'user@tea.com',
                'password' => 'abcd@1234',
                'role' => 'user',
            ],
        ];

        foreach ($systemUsers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                ]
            );

            $user->syncRoles([$data['role']]);
        }

        /**
         * =========================
         * 3. HR / KDC / TROLY USERS
         * =========================
         */

        $extraUsers = [
            [
                'name' => 'HR User',
                'email' => 'hr@tea.com',
                'role' => 'manager',
            ],
            [
                'name' => 'Loan Trần',
                'email' => 'loantran@tocotocotea.com',
                'role' => 'manager',
            ],
            [
                'name' => 'Hạnh Hồ',
                'email' => 'hanhho@tocotocotea.com',
                'role' => 'manager',
            ],
            [
                'name' => 'KDC User',
                'email' => 'kdc@tea.com',
                'role' => 'manager',
            ],
            [
                'name' => 'Trợ Lý 1',
                'email' => 'troly1@tea.com',
                'role' => 'manager',
            ],
            [
                'name' => 'Trợ Lý 2',
                'email' => 'troly2@tea.com',
                'role' => 'manager',
            ],
        ];

        foreach ($extraUsers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('12345678'),
                ]
            );

            $user->syncRoles([$data['role']]);
        }

        /**
         * =========================
         * 4. TECHNICIAN FROM CONFIG
         * (merged from BPXDRoleSeeder)
         * =========================
         */

        $technicians = config('technician', []);

        foreach ($technicians as $tech) {
            if (!is_array($tech) || !isset($tech['email']) || !isset($tech['name'])) {
                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $tech['email']],
                [
                    'name' => $tech['name'],
                    'password' => Hash::make('12345678'),
                ]
            );

            // phân role theo email đặc biệt
            if (in_array($tech['email'], [
                'liemhoang.support.hcm@tocototea.com',
                'dunguyen.support@tocotocotea.com',
            ])) {
                $user->syncRoles(['manager']);
            } else {
                $user->syncRoles(['technician']);
            }
        }

        /**
         * =========================
         * 5. INTERNAL AUDIT USERS (KTNB)
         * (merged from KTNBRoleSeeder)
         * =========================
         */

        $ktnbUsers = [
            [
                'email' => 'ktnb1@tocotocotea.com',
                'name' => 'KTNB 1',
            ],
            [
                'email' => 'ktnb2@tocotocotea.com',
                'name' => 'KTNB 2',
            ],
        ];

        foreach ($ktnbUsers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('12345678'),
                ]
            );

            $user->syncRoles(['manager']);
        }
    }
}