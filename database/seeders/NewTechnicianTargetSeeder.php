<?php

namespace Database\Seeders;

use App\Models\TechnicianTarget;
use Illuminate\Database\Seeder;

class NewTechnicianTargetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TechnicianTarget::updateOrCreate(
            [
                'technician_name' => 'Ngô Long Vũ',
                'technician_email' => 'vungo.support@tocotocotea.com',
            ],
            [
                'store_count' => 0,
                'daily_target' => 0,
                'monthly_target' => 120,
            ]
        );

        $this->command->info('Đã thêm technician mới.');
    }
}
