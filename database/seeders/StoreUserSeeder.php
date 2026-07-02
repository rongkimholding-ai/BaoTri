<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class StoreUserSeeder extends Seeder
{
    public function run(): void
    {
        $paths = [
            resource_path('json/stores.json'),
            resource_path('json/stores_mn.json'),
            resource_path('json/stores_cici_mb.json'),
            resource_path('json/stores_cici_mn.json'),
        ];

        $stores = [];

        foreach ($paths as $path) {
            if (!File::exists($path)) {
                $this->command->error("Không tìm thấy file: {$path}");
                continue;
            }

            $data = json_decode(File::get($path), true);

            if (!is_array($data)) {
                $this->command->error(basename($path) . ' không đúng định dạng');
                continue;
            }

            $stores = array_merge($stores, $data);
        }

        if (empty($stores)) {
            $this->command->error('Không có dữ liệu cửa hàng hợp lệ để tạo tài khoản');
            return;
        }

        /**
         * ROLE
         */
        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        $count = 0;

        foreach ($stores as $store) {

            /**
             * =========================
             * 1. STORE USER (USER ROLE)
             * =========================
             */
            $email = trim($store['email'] ?? '');

            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $store['name'] ?? $email,
                        'password' => Hash::make('12345678'),
                    ]
                );

                $user->syncRoles([$userRole->name]);
                $count++;
            }

            /**
             * =========================
             * 2. AM EMAIL -> MANAGER
             * =========================
             */
            $amEmail = trim($store['am_email'] ?? '');

            if (!empty($amEmail) && filter_var($amEmail, FILTER_VALIDATE_EMAIL)) {

                $amUser = User::updateOrCreate(
                    ['email' => $amEmail],
                    [
                        'name' => $store['am_name'] ?? $amEmail,
                        'password' => Hash::make('12345678'),
                    ]
                );

                $amUser->syncRoles('am');
                $count++;
            }

            /**
             * =========================
             * 3. OM EMAIL -> MANAGER
             * =========================
             */
            $omEmail = trim($store['om_email'] ?? '');

            if (!empty($omEmail) && filter_var($omEmail, FILTER_VALIDATE_EMAIL)) {

                $omUser = User::updateOrCreate(
                    ['email' => $omEmail],
                    [
                        'name' => $store['om_name'] ?? $omEmail,
                        'password' => Hash::make('12345678'),
                    ]
                );

                $omUser->syncRoles('om');
                $count++;
            }
        }

        $this->command->info("Da xu ly {$count} tai khoan.");
    }
}