<?php
namespace App\Models;

use App\Models\MaintenanceRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
class MaintenanceRequestsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithStrictNullComparison
{
    public function collection()
    {
        return MaintenanceRequest::select([
            'id',
            'branch_code',
            'branch_name',
            'request_date',
            'item_category',
            'issue_description',
            'standard_completion_time',
            'severity',
            'technician_name',
            'solution_description',
            'actual_completion_date',
            'actual_duration',
            'sla_status',
            'delay_reason',
            'outsourced_provider',
            'acceptance_result',
            'acceptance_confirmed_by',
            // 'created_at',
            // 'updated_at',
        ])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Mã cơ sở',
            'Tên cơ sở',
            'Ngày yêu cầu',
            'Hạng mục',
            'Diễn giải sự cố',
            'Thời gian QC',
            'Loại sự cố',
            'Kỹ thuật viên',
            'Khắc phục',
            'Ngày hoàn thành',
            'Thời gian TT',
            'SLA',
            'Lý do trễ',
            'Nhà cung cấp',
            'Nghiệm thu',
            'Người xác nhận',
            // 'Ngày tạo',
            // 'Ngày cập nhật',
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

        // ===== Highlight SLA =====
        // cột SLA = 13 (tính từ A)
        for ($row = 2; $row <= $highestRow; $row++) {

            $sla = $sheet->getCell("M{$row}")->getValue();

            if ($sla === 'Trễ hạn') {
                $sheet->getStyle("M{$row}")
                    ->getFont()
                    ->getColor()
                    ->setRGB('FF0000');

                $sheet->getStyle("M{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('FFE5E5');
            }

            if ($sla === 'Đúng hạn') {
                $sheet->getStyle("M{$row}")
                    ->getFont()
                    ->getColor()
                    ->setRGB('00B050');
            }
        }

        return [];
    }
}