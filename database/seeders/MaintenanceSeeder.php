<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceRequest;

class MaintenanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // MaintenanceRequest::create([
        //     'branch_code' => 'LSR001',
        //     'branch_name' => 'Cơ sở Hà Nội',
        //     'request_date' => now(),
        //     'issue_description' => 'Máy lạnh không hoạt động',
        //     'standard_completion_time' => 24,
        //     'severity' => '3B',
        //     'technician_name' => 'Nguyễn Văn A',
        //     'solution_description' => 'Thay tụ máy lạnh',
        //     'actual_completion_date' => now(),
        //     'actual_duration' => 6,
        //     'sla_status' => 'Đúng hạn',
        //     'delay_reason' => null,
        //     'outsourced_provider' => null,
        //     'acceptance_result' => 'Đạt',
        //     'acceptance_confirmed_by' => 'Trần Văn B',
        // ]);
    }
}
