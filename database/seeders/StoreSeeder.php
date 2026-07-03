<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paths_mb = [
            resource_path('json/stores.json'),
            resource_path('json/stores_cici_mb.json'),
        ];

        $paths_mn = [
            resource_path('json/stores_mn.json'),
            resource_path('json/stores_cici_mn.json'),
        ];

        $stores_mb = [];
        $stores_mn = [];

        /* Miền Bắc */
        foreach ($paths_mb as $path) {
            if (!file_exists($path)) {
                $this->command->error("Không tìm thấy file: {$path}");
                continue;
            }

            $data = json_decode(file_get_contents($path), true);

            if (!is_array($data)) {
                $this->command->error(basename($path) . ' không đúng định dạng');
                continue;
            }

            $stores_mb = array_merge($stores_mb, $data);
        }

        foreach ($stores_mb as $store) {
            Store::updateOrCreate(
                [
                    'email' => $store['email'],
                ],
                [
                    'code' => $store['code'] ?? null,
                    'name' => $store['name'],
                    'area' => 'north',
                    'region' => $store['region'] ?? null,
                    'am_name' => $store['am_name'] ?? null,
                    'am_email' => $store['am_email'] ?? null,
                    'om_name' => $store['om_name'] ?? null,
                    'om_email' => $store['om_email'] ?? null,
                    'technician_name' => $store['technician_name'] ?? null,
                    'muasam_email' => $store['muasam_email'] ?? null,
                ]
            );
        }

        /* Miền Nam */
        foreach ($paths_mn as $path) {
            if (!file_exists($path)) {
                $this->command->error("Không tìm thấy file: {$path}");
                continue;
            }

            $data = json_decode(file_get_contents($path), true);

            if (!is_array($data)) {
                $this->command->error(basename($path) . ' không đúng định dạng');
                continue;
            }

            $stores_mn = array_merge($stores_mn, $data);
        }

        foreach ($stores_mn as $store) {
            Store::firstOrCreate(
                [
                    'email' => $store['email'],
                ],
                [
                    'code' => $store['code'] ?? null,
                    'name' => $store['name'],
                    'area' => 'south',
                    'region' => $store['region'] ?? null,
                    'am_name' => $store['am_name'] ?? null,
                    'am_email' => $store['am_email'] ?? null,
                    'om_name' => $store['om_name'] ?? null,
                    'om_email' => $store['om_email'] ?? null,
                    'technician_name' => $store['technician_name'] ?? null,
                    'muasam_email' => $store['muasam_email'] ?? null,
                ]
            );
        }

        $this->command->info('Import stores thành công.');
    }
}
