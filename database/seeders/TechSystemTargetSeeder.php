<?php

namespace Database\Seeders;

use App\Models\TechSystemTarget;
use Illuminate\Database\Seeder;

class TechSystemTargetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $count = 0;
        foreach (config('technician_ht', []) as $technician) {
            TechSystemTarget::updateOrCreate(
                [
                    'technician_name' => trim($technician['name']),
                    'technician_email' => trim($technician['email']),
                ],
                [
                    'store_count' => 0,
                    'daily_target' => 0,
                    'monthly_target' => 0,
                ]
            );
            $count++;
        }
        $this->command->info("Da xu ly {$count} ban ghi bao cao.");
    }
}