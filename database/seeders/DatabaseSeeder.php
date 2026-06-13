<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\MaintenanceSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StoreUserSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // MaintenanceSeeder::class,
            TechnicianTargetSeeder::class,
            RolePermissionSeeder::class,
            DefaultUsersSeeder::class,
            StoreUserSeeder::class,
            HolidayCalendarSeeder::class,
        ]);
    }
}
