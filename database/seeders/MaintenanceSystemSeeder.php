<?php

namespace Database\Seeders;

use App\Models\MaintenanceSystem;
use Illuminate\Database\Seeder;

class MaintenanceSystemSeeder extends Seeder
{
    public function run(): void
    {
        MaintenanceSystem::truncate();

        MaintenanceSystem::create([
            'issue_code' => 'SYS001',
            'issue_name' => 'Máy POS không khởi động',

            'issue_description' =>
                'Máy POS tại cơ sở không lên nguồn sau khi bật.',

            'solution_description' =>
                'Kiểm tra nguồn điện, adapter và thay thế adapter mới nếu hỏng.',

            'branch_code' => 'HN001',
            'branch_name' => 'TocoToco Hồ Gươm',
            'branch_email' => 'hoguom@tocotocotea.com',

            'technician_name' => 'Nguyễn Văn A',
            'technician_email' => 'support.hn@tocotocotea.com',
            'technician_mobile' => '0901234567',

            'request_date' => now(),

            'actual_completion_date' => '2_8_HOURS',

            'status' => 'NEW',

            'delay_reason' => null,

            'completed_at' => null,

            'created_by' => 'admin@tocotocotea.com',
            'updated_by' => 'admin@tocotocotea.com',
            'completed_by' => null,
        ]);
    }
}