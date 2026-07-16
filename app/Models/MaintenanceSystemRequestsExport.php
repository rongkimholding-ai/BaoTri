<?php
namespace App\Models;

use App\Models\MaintenanceSystem;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MaintenanceSystemRequestsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithStrictNullComparison, WithMapping
{
    protected $from_date;
    protected $to_date;
    protected $from_date_completed; // nullable
    protected $to_date_completed;   // nullable
    protected $techEmails;

    public function __construct($from_date, $to_date, $from_date_completed, $to_date_completed, array $techEmails = [])
    {
        $this->from_date = Carbon::parse($from_date)->startOfDay();
        $this->to_date = Carbon::parse($to_date)->endOfDay();

        // Có thể null nên chỉ parse khi có giá trị
        $this->from_date_completed = !empty($from_date_completed) ? Carbon::parse($from_date_completed)->startOfDay() : null;
        $this->to_date_completed = !empty($to_date_completed) ? Carbon::parse($to_date_completed)->endOfDay() : null;
        $this->techEmails = $techEmails;
    }

    public function collection()
    {
        // Đúng tên cột của bảng MaintenanceSystem
        $query = MaintenanceSystem::select([
            'id',
            'branch_code',
            'branch_name',
            'request_date',
            'issue_name', 
            'issue_description',
            'standard_completion_time',
            'technician_name',
            'solution_description',
            'actual_completion_date',
            'actual_duration',
            'status', 
            'delay_reason',
            'acceptance_result',
            'acceptance_confirmed_by',
        ])
        ->whereBetween(
            'request_date',
            [$this->from_date, $this->to_date]
        );

        // Chỉ filter by actual_completion_date khi có truyền from/to
        if (!is_null($this->from_date_completed) && !is_null($this->to_date_completed)) {
            $query->whereBetween(
                'actual_completion_date',
                [$this->from_date_completed, $this->to_date_completed]
            );
        } elseif (!is_null($this->from_date_completed)) {
            $query->where(
                'actual_completion_date',
                '>=',
                $this->from_date_completed
            );
        } elseif (!is_null($this->to_date_completed)) {
            $query->where(
                'actual_completion_date',
                '<=',
                $this->to_date_completed
            );
        }

        if (!empty($this->techEmails)) {
            $query->whereIn(
                'technician_email',
                $this->techEmails
            );
        }

        $data = $query->get();

        return $data;
    }

    public function map($row): array
    {
        $sla_status = config('sla_status.names_ht');
        $acceptance = collect(config('acceptance'))->keyBy('key')->map(function($item) { return $item['name']; });
        $real_time = collect(config('real_time_ht'))->keyBy('key')->map(function($item) { return $item['name']; });

        return [
            $row->id,                                     // ID
            $row->branch_code,                            // Mã cơ sở
            $row->branch_name,                            // Tên cơ sở
            $row->request_date,                           // Ngày yêu cầu
            $row->issue_name,                             // Tên sự cố
            $row->issue_description,                      // Diễn giải sự cố
            $real_time[$row->standard_completion_time] ?? $row->standard_completion_time, // Thời gian QC/mapping
            $row->technician_name,                         // Kỹ thuật viên
            $row->solution_description,                    // Khắc phục
            $row->actual_completion_date,                  // Ngày hoàn thành
            $row->actual_duration,                         // Thời gian TT
            $sla_status[$row->status] ?? $row->status,     // SLA (map)
            $row->delay_reason,                            // Lý do trễ
            $acceptance[$row->acceptance_result] ?? $row->acceptance_result, // Nghiệm thu (map)
            $row->acceptance_confirmed_by,                 // Người xác nhận
            // $row->created_at,
            // $row->updated_at,
        ];
    }

    public function headings(): array
    {
        return [
            'ID',                 // 1
            'Mã cơ sở',           // 2
            'Tên cơ sở',          // 3
            'Ngày yêu cầu',       // 4
            'Tên sự cố',          // 5
            'Diễn giải sự cố',    // 6
            'Thời gian QC',       // 7
            'Kỹ thuật viên',      // 8
            'Khắc phục',          // 9
            'Ngày hoàn thành',    // 10
            'Thời gian TT',       // 11
            'SLA',                // 12
            'Lý do trễ',          // 13
            'Nghiệm thu',         // 14
            'Người xác nhận',     // 15
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
                'color' => ['rgb' => '262626'], // Đậm hơn, gần với #222
                'size' => 13,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFE699'], // vàng pastel nhẹ cho header
            ],
        ]);

        // ===== Border toàn bảng =====
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'C1C1C1'], // border mờ hơn
                    ],
                ],
            ]);

        // ===== Auto row height =====
        foreach (range(2, $highestRow) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        // ===== Highlight SLA =====
        // Cột SLA => Thứ 12 (Tức là L), mapping đúng map ở trên
        for ($row = 2; $row <= $highestRow; $row++) {
            $sla = $sheet->getCell("L{$row}")->getValue();

            if ($sla === 'Trễ hạn') {
                $sheet->getStyle("L{$row}")
                    ->getFont()
                    ->getColor()
                    ->setRGB('C00000'); // Đỏ chuẩn hơn, rõ hơn

                $sheet->getStyle("L{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('FDE9D9'); // cam nhạt để nổi bật
            }

            if ($sla === 'Đúng hạn') {
                $sheet->getStyle("L{$row}")
                    ->getFont()
                    ->getColor()
                    ->setRGB('228B22'); // Xanh forest cho nổi bật
            }
        }

        return [];
    }
}