<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class VPMBUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = resource_path('json/mail_vp_mb.json');

        if (!File::exists($path)) {
            $this->command->error("Không tìm thấy file: {$path}");
            return;
        }

        $users = json_decode(File::get($path), true);

        if (!is_array($users)) {
            $this->command->error("File JSON không đúng định dạng.");
            return;
        }

        $count = 0;

        foreach ($users as $data) {

            if (empty($data['email'])) {
                continue;
            }

            $user = User::firstOrCreate(
                [
                    'email' => trim($data['email']),
                ],
                [
                    'name'     => $data['fullName'],
                    'password' => Hash::make('12345678'),
                ]
            );

            // Nếu user đã tồn tại thì cập nhật lại tên
            // if ($user->name !== ($data['fullName'] ?? '')) {
            //     $user->update([
            //         'name' => $data['fullName'],
            //     ]);
            // }

            // Gán role
            $user->syncRoles(['viewer']);

            $count++;
        }

        $this->command->info("Đã xử lý {$count} vp mb user.");
    }
}