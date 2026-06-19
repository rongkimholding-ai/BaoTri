<?php

namespace App\Exports;

use App\Models\MaintenanceRequest;
use App\Models\TechnicianTarget;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
class TechnicianReportExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithStrictNullComparison
{
    protected $from_date;
    protected $to_date;

    public function __construct($from_date, $to_date)
    {
        $this->from_date = Carbon::parse($from_date)->startOfDay();
        $this->to_date = Carbon::parse($to_date)->endOfDay();
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Lấy danh sách technicians từ technician_targets (mỗi người 1 record)
        $technicians = TechnicianTarget::all();

        // Lấy maintenance_requests với mọi technician (không group chung tên)
        $requestsRaw = MaintenanceRequest::query()
            ->whereBetween('request_date', [$this->from_date, $this->to_date])
            ->where('technician_email', '!=', 'liemhoang.support.hcm@tocotocotea.com')
            ->get();

        // Group đúng từng technician theo unique key (ưu tiên id hoặc sử dụng tên/email nếu unique)
        // Ở đây sẽ group theo technician_name + (technician_email nếu có để chính xác)
        $keyBy = function ($item) {
            // Nếu có field email, dùng cả tên+email, nếu không chỉ technician_name
            return $item->technician_name . '|' . ($item->technician_email ?? '');
        };

        $requestsByTech = $requestsRaw->groupBy($keyBy);

        // Map theo technician_targets: mỗi record chỉ thống kê cho đúng person
        $requests = $technicians->map(function ($tech) use ($requestsByTech) {
            $key = $tech->technician_name . '|' . ($tech->technician_email ?? '');

            $requests = $requestsByTech->get($key, collect());
            $totalCompleted = $requests->count();

            $monthlyTarget = (int) $tech->monthly_target;

            // Số đúng hạn (sla_status COMPLETED): 
            $dung_han_count = $requests
                ->where('sla_status', config('sla_status.code.COMPLETED'))->count();

            // Số KHÔNG đúng hạn: tất cả bản ghi completion nhưng sla_status != COMPLETED
            $khong_dung_han_count = $requests
                ->where('sla_status', '!=', config('sla_status.code.COMPLETED'))->count();

            $quality_pass_count = $requests->where('acceptance_result', 'accepted')->count();
            $quality_fail_count = $requests->where(function ($item) {
                return $item->acceptance_result === 'rejected' || is_null($item->acceptance_result);
            })->count();

            // Số lượng ngoài giờ
            $ngoai_gio_count = $requests->where('is_off_worktime', true)->count();

            $completion_percent =
                $monthlyTarget > 0
                ? round($totalCompleted * 100 / $monthlyTarget, 2)
                : 0;

            $dung_han_dm_percent =
                $monthlyTarget > 0
                ? round($dung_han_count * 100 / $monthlyTarget, 2)
                : 0;

            $dung_han_total_percent =
                $totalCompleted > 0
                ? round($dung_han_count * 100 / $totalCompleted, 2)
                : 0;

            $khong_dung_han_percent =
                $totalCompleted > 0
                ? round($khong_dung_han_count * 100 / $totalCompleted, 2)
                : 0;

            $quality_pass_dm_percent =
                $monthlyTarget > 0
                ? round($quality_pass_count * 100 / $monthlyTarget, 2)
                : 0;

            $quality_pass_total_percent =
                $totalCompleted > 0
                ? round($quality_pass_count * 100 / $totalCompleted, 2)
                : 0;

            $quality_fail_total_percent =
                $totalCompleted > 0
                ? round($quality_fail_count * 100 / $totalCompleted, 2)
                : 0;

            return (object) [
                'technician_name' => $tech->technician_name,
                'store_count' => (int) $tech->store_count,
                'daily_target' => (int) $tech->daily_target,
                'monthly_target' => (int) $tech->monthly_target,

                'total_completed' => (int) $totalCompleted,
                'completion_percent' => (float) $completion_percent,

                // Số lượng ngoài giờ
                'ngoai_gio_count' => (int) $ngoai_gio_count,

                'dung_han_count' => (int) $dung_han_count,
                'dung_han_dm_percent' => (float) $dung_han_dm_percent,
                'dung_han_total_percent' => (float) $dung_han_total_percent,

                'khong_dung_han_count' => (int) $khong_dung_han_count,
                'khong_dung_han_percent' => (float) $khong_dung_han_percent,

                'quality_pass_count' => (int) $quality_pass_count,
                'quality_pass_dm_percent' => (float) $quality_pass_dm_percent,
                'quality_pass_total_percent' => (float) $quality_pass_total_percent,

                'quality_fail_count' => (int) $quality_fail_count,
                'quality_fail_total_percent' => (float) $quality_fail_total_percent,
            ];
        });

        return $requests;
    }

    public function headings(): array
    {
        return [
            'Kỹ thuật viên',

            'Số CH phụ trách',
            'Định mức/ngày',
            'Định mức/tháng',

            'Tổng sự vụ',
            'Tỷ lệ sự vụ/ĐM (%)',

            'SL ngoài giờ',

            'Đúng hạn',
            'Tỷ lệ Đúng hạn/ĐM tháng (%)',
            'Tỷ lệ Đúng hạn/Tổng TH (%)',

            'Trễ hạn',
            'Tỷ lệ Trễ hạn/Tổng TH (%)',

            'Đạt nghiệm thu',
            'Tỷ lệ đạt CL/ĐM tháng (%)',
            'Tỷ lệ đạt CL/Tổng TH (%)',

            'Không đạt nghiệm thu',
            'Tỷ lệ không đạt CL/Tổng TH (%)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // ===== Freeze header =====
        $sheet->freezePane('A2');

        // ===== Header style =====
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
                'size' => 13,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFD700'], // vàng tiêu chuẩn
            ],

        ]);

        // ===== Border toàn bảng =====
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
            ]);

        // ===== Auto row height =====
        foreach (range(2, $highestRow) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(18);
        }
        return [];
    }
}
