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
   

        // Tạo role user nếu chưa có
        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        $count = 0;

        foreach ($stores as $store) {

            $email = trim($store['email'] ?? '');

            if (
                empty($email) ||
                ! filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                continue;
            }

            $user = User::updateOrCreate(
                [
                    'email' => $email,
                ],
                [
                    'name' => $store['name'] ?? $email,
                    'password' => Hash::make('12345678'),
                ]
            );

            $user->syncRoles([$role->name]);

            $count++;
        }

        $this->command->info("Da xu ly {$count} tai khoan.");
    }
}
