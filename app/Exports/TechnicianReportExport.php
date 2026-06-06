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

class TechnicianReportExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
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

                COUNT(*) as total,

                SUM(
                    CASE
                        WHEN sla_status = 'Đúng hạn'
                        THEN 1
                        ELSE 0
                    END
                ) as dung_han_count,

                SUM(
                    CASE
                        WHEN sla_status <> 'Đúng hạn'
                        OR sla_status IS NULL
                        THEN 1
                        ELSE 0
                    END
                ) as con_lai_count,

                ROUND(
                    SUM(
                        CASE
                            WHEN sla_status = 'Đúng hạn'
                            THEN 1
                            ELSE 0
                        END
                    ) * 100 / COUNT(*),
                    2
                ) as dung_han_percent,

                ROUND(
                    SUM(
                        CASE
                            WHEN sla_status <> 'Đúng hạn'
                            OR sla_status IS NULL
                            THEN 1
                            ELSE 0
                        END
                    ) * 100 / COUNT(*),
                    2
                ) as con_lai_percent,

                technician_targets.store_count,
                technician_targets.daily_target,
                technician_targets.monthly_target,

                CASE
                    WHEN COUNT(*) - COALESCE(technician_targets.monthly_target, 0) < 0
                    THEN 0
                    ELSE COUNT(*) - COALESCE(technician_targets.monthly_target, 0)
                END as vuot_dinh_muc
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
                return [
                    'Kỹ thuật viên' => $item->technician_name,
                    
                    'Số CH phụ trách' => $item->store_count,
                    'ĐM/ngày' => $item->daily_target,
                    'ĐM/tháng' => $item->monthly_target,

                    'Tổng yêu cầu' => $item->total,
                    'Vượt định mức' => $item->vuot_dinh_muc,

                    'Đúng hạn' => $item->dung_han_count,
                    '% Đúng hạn' => $item->dung_han_percent,

                    'Không đúng hạn' => $item->con_lai_count,
                    '% Không đúng hạn' => $item->con_lai_percent,

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

            'Tổng yêu cầu',
            'Vượt định mức',

            'Đúng hạn',
            '% Đúng hạn',

            'Không đúng hạn',
            '% Không đúng hạn',
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
