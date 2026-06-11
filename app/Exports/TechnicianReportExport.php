<?php

namespace App\Exports;

use App\Models\MaintenanceRequest;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TechnicianReportExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $startDate = Carbon::parse(
            $this->month . '-01'
        )->startOfMonth();

        $endDate = Carbon::parse(
            $this->month . '-01'
        )->endOfMonth();
        return MaintenanceRequest::query()
            ->leftJoin(
                'technician_targets',
                'maintenance_requests.technician_name',
                '=',
                'technician_targets.technician_name'
            )
            ->selectRaw("
                maintenance_requests.technician_name,

                technician_targets.store_count,
                technician_targets.daily_target,
                technician_targets.monthly_target,

                SUM(
                    CASE
                        WHEN actual_completion_date IS NOT NULL
                        THEN 1
                        ELSE 0
                    END
                ) as total_completed,

                SUM(
                    CASE
                        WHEN sla_status = '" . config('sla_status.code.COMPLETED') . "'
                        THEN 1
                        ELSE 0
                    END
                ) as dung_han_count,

                SUM(
                    CASE
                        WHEN actual_completion_date IS NOT NULL
                        AND (
                            sla_status <> '" . config('sla_status.code.COMPLETED') . "'
                            OR sla_status IS NULL
                        )
                        THEN 1
                        ELSE 0
                    END
                ) as khong_dung_han_count,

                SUM(
                    CASE
                        WHEN acceptance_result = 'Đạt'
                        THEN 1
                        ELSE 0
                    END
                ) as quality_pass_count,

                SUM(
                    CASE
                        WHEN acceptance_result = 'Không đạt'
                        THEN 1
                        ELSE 0
                    END
                ) as quality_fail_count
            ")
            ->whereBetween(
                'maintenance_requests.request_date',
                [$startDate, $endDate]
            )
            ->groupBy(
                'maintenance_requests.technician_name',
                'technician_targets.store_count',
                'technician_targets.daily_target',
                'technician_targets.monthly_target'
            )
            ->get()
            ->map(function ($item) {

                $monthlyTarget = (int) $item->monthly_target;

                $totalCompleted = (int) $item->total_completed;

                return [

                    'Kỹ thuật viên'
                    => $item->technician_name,

                    'Số CH phụ trách'
                    => $item->store_count,

                    'Định mức/ngày'
                    => $item->daily_target,

                    'Định mức/tháng'
                    => $item->monthly_target,

                    'Tổng số vụ hoàn thành'
                    => $item->total_completed,

                    'Tỷ lệ hoàn thành/ĐM'
                    => $this->percent($item->total_completed, $monthlyTarget),

                    'Đạt thời gian'
                    => $item->dung_han_count,

                    'Tỷ lệ đạt TG/ĐM tháng'
                    => $this->percent($item->dung_han_count, $monthlyTarget),

                    'Tỷ lệ đạt TG/Tổng TH'
                    => $this->percent($item->dung_han_count, $totalCompleted),

                    'Không đạt thời gian'
                    => $item->khong_dung_han_count,

                    'Tỷ lệ không đạt TG/Tổng TH'
                    => $this->percent($item->khong_dung_han_count, $totalCompleted),

                    'Đạt nghiệm thu'
                    => $item->quality_pass_count,

                    'Tỷ lệ đạt CL/ĐM tháng'
                    => $this->percent($item->quality_pass_count, $monthlyTarget),

                    'Tỷ lệ đạt CL/Tổng TH'
                    => $this->percent($item->quality_pass_count, $totalCompleted),

                    'Không đạt nghiệm thu'
                    => $item->quality_fail_count,

                    'Tỷ lệ không đạt CL/Tổng TH'
                    => $this->percent($item->quality_fail_count, $totalCompleted),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Kỹ thuật viên',

            'Số CH phụ trách',
            'Định mức/ngày',
            'Định mức/tháng',

            'Tổng số vụ hoàn thành',
            'Tỷ lệ hoàn thành/ĐM',

            'Đạt thời gian',
            'Tỷ lệ đạt TG/ĐM tháng',
            'Tỷ lệ đạt TG/Tổng TH',

            'Không đạt thời gian',
            'Tỷ lệ không đạt TG/Tổng TH',

            'Đạt nghiệm thu',
            'Tỷ lệ đạt CL/ĐM tháng',
            'Tỷ lệ đạt CL/Tổng TH',

            'Không đạt nghiệm thu',
            'Tỷ lệ không đạt CL/Tổng TH',
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

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_PERCENTAGE_00,
            'H' => NumberFormat::FORMAT_PERCENTAGE_00,
            'I' => NumberFormat::FORMAT_PERCENTAGE_00,
            'K' => NumberFormat::FORMAT_PERCENTAGE_00,
            'M' => NumberFormat::FORMAT_PERCENTAGE_00,
            'N' => NumberFormat::FORMAT_PERCENTAGE_00,
            'O' => NumberFormat::FORMAT_PERCENTAGE_00,
        ];
    }

    private function percent($value, $base)
    {
        if (!$base || $base == 0) {
            return 0;
        }
    
        return round($value / $base, 6); // giữ precision tốt cho BI
    }
}
