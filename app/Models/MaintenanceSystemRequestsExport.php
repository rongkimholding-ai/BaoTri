<?php

namespace App\Models;

use App\Models\MaintenanceSystem;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MaintenanceSystemRequestsExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithStrictNullComparison,
    WithMapping
{
    protected $from_date, $to_date, $from_date_completed, $to_date_completed, $techEmails;

    public function __construct(
        $from_date,
        $to_date,
        $from_date_completed = null,
        $to_date_completed = null,
        array $techEmails = []
    ) {
        $this->from_date = Carbon::parse($from_date)->startOfDay();
        $this->to_date = Carbon::parse($to_date)->endOfDay();

        $this->from_date_completed = $from_date_completed
            ? Carbon::parse($from_date_completed)->startOfDay()
            : null;

        $this->to_date_completed = $to_date_completed
            ? Carbon::parse($to_date_completed)->endOfDay()
            : null;

        // Normalize emails: lower, trim, remove empty
        $this->techEmails = array_values(array_filter(array_map(static function ($email) {
            return strtolower(trim($email));
        }, $techEmails)));
    }

    /**
     * Lấy dữ liệu export
     */
    public function collection()
    {
        $query = MaintenanceSystem::query()
            ->with(['store:code,latitude,longitude'])
            ->select([
                'id',
                'branch_code',
                'branch_name',
                'request_date',
                'issue_name',
                'issue_description',
                'standard_completion_time',
                'work_type',
                'complete_latitude',
                'complete_longitude',
                'gps_at',
                'technician_name',
                'solution_description',
                'actual_completion_date',
                'actual_duration',
                'status',
                'delay_reason',
                'acceptance_result',
                'acceptance_confirmed_by',
                'technician_email',
            ])
            ->whereBetween('request_date', [$this->from_date, $this->to_date]);

        if ($this->from_date_completed && $this->to_date_completed) {
            $query->whereBetween('actual_completion_date', [$this->from_date_completed, $this->to_date_completed]);
        } elseif ($this->from_date_completed) {
            $query->where('actual_completion_date', '>=', $this->from_date_completed);
        } elseif ($this->to_date_completed) {
            $query->where('actual_completion_date', '<=', $this->to_date_completed);
        }

        if (!empty($this->techEmails)) {
            $query->whereIn('technician_email', $this->techEmails);
        }

        return $query->orderBy('request_date')->get();
    }

    /**
     * Mapping dữ liệu ra Excel
     */
    public function map($row): array
    {
        static $slaStatus, $acceptance, $realTime;
        if ($slaStatus === null) {
            $slaStatus = config('sla_status.names_ht');
            $acceptance = collect(config('acceptance'))->pluck('name', 'key');
            $realTime = collect(config('real_time_ht'))->pluck('name', 'key');
        }

        $workType = match ((int)$row->work_type) {
            1 => 'Offline',
            2 => 'Online',
            default => '',
        };

        $distanceText = isset($row->distance)
            ? number_format($row->distance, 2) . ' m'
            : '';

        return [
            $row->id,
            $row->branch_code,
            $row->branch_name,
            $row->request_date,
            $row->issue_name,
            $workType,
            $row->issue_description,
            $realTime[$row->standard_completion_time] ?? $row->standard_completion_time,
            !empty($distanceText) ? ($row->branch_name . ' (' . $distanceText . ')') : '',
            $row->technician_name,
            $row->solution_description,
            $row->actual_completion_date,
            $row->actual_duration,
            $slaStatus[$row->status] ?? $row->status,
            $row->delay_reason,
            $acceptance[$row->acceptance_result] ?? $row->acceptance_result,
            $row->acceptance_confirmed_by,
        ];
    }

    /**
     * Header Excel
     */
    public function headings(): array
    {
        return [
            'ID',
            'Mã cơ sở',
            'Tên cơ sở',
            'Ngày yêu cầu',
            'Tên sự cố',
            'Loại CV',
            'Diễn giải sự cố',
            'Thời gian QC',
            'Xác nhận địa điểm Offline',
            'Kỹ thuật viên',
            'Khắc phục',
            'Ngày hoàn thành',
            'Thời gian TT',
            'SLA',
            'Lý do trễ',
            'Nghiệm thu',
            'Người xác nhận',
        ];
    }

    /**
     * Style Excel
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Freeze header & Autofilter
        $sheet->freezePane('A2');
        if ($highestRow >= 1) $sheet->setAutoFilter("A1:Q{$highestRow}");

        // Header style
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '262626']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'B7B7B7'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        // Body border
        if ($highestRow >= 2) {
            $sheet->getStyle("A2:Q{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Widths
        $widths = [
            'A' => 8, 'B' => 14, 'C' => 25, 'D' => 18, 'E' => 28, 'F' => 12,
            'G' => 40, 'H' => 18, 'I' => 25, 'J' => 20, 'K' => 40,
            'L' => 18, 'M' => 14, 'N' => 13, 'O' => 30, 'P' => 18, 'Q' => 22,
        ];
        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // Các cột text dài
        // Thêm cột I nếu có distanceText thì format text dài
        $longTextColumns = ['C', 'E', 'G', 'H', 'K', 'O'];

        // Xác định những dòng nào ở cột I có distanceText để set text dài
        $distanceRows = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $distanceCell = $sheet->getCell("I{$row}")->getValue();
            if (!empty($distanceCell)) {
                $distanceRows[] = $row;
            }
        }

        foreach ($longTextColumns as $column) {
            $sheet->getStyle("{$column}2:{$column}{$highestRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);
        }
        // Áp dụng style text dài cho các row cột I có distanceText
        foreach ($distanceRows as $rowIdx) {
            $sheet->getStyle("I{$rowIdx}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);
        }

        // Text trái - center
        foreach (['J', 'Q'] as $column) {
            $sheet->getStyle("{$column}2:{$column}{$highestRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Các cột căn giữa
        foreach (['A', 'B', 'D', 'F', 'I', 'L', 'M', 'N', 'P'] as $column) {
            $sheet->getStyle("{$column}2:{$column}{$highestRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Quick lambda: chuẩn hóa và đếm số dòng
        $countLines = function ($val, $perLine) {
            if ($val === '') return 1;
            $lines = 0;
            foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", (string)$val)) as $line) {
                $lines += max(1, (int)ceil(mb_strlen($line) / $perLine));
            }
            return max(1, $lines);
        };

        // Tự động tính chiều cao từng row, highlight SLA & WorkType cùng loop
        for ($row = 2; $row <= $highestRow; $row++) {
            $branchName       = $sheet->getCell("C{$row}")->getValue();
            $issueName        = $sheet->getCell("E{$row}")->getValue();
            $issueDescription = $sheet->getCell("G{$row}")->getValue();
            $realTime         = $sheet->getCell("H{$row}")->getValue();
            $solution         = $sheet->getCell("K{$row}")->getValue();
            $delayReason      = $sheet->getCell("O{$row}")->getValue();
            $distanceText     = $sheet->getCell("I{$row}")->getValue();

            // Nếu cột I là text dài, set nhiều ký tự hơn
            $countI = (!empty($distanceText)) ? $countLines($distanceText, 45) : 1;

            // Calculate max line count
            $lines = max(
                $countLines($branchName, 32),
                $countLines($issueName, 36),
                $countLines($issueDescription, 52),
                $countLines($realTime, 23),
                $countLines($solution, 52),
                $countLines($delayReason, 39),
                $countI
            );

            // Chiều cao tối thiểu 30, tối đa 150
            $height = min(150, max(30, $lines * 15));
            $sheet->getRowDimension($row)->setRowHeight($height);

            // Highlight SLA
            $sla = $sheet->getCell("N{$row}")->getValue();
            if ($sla === 'Trễ hạn') {
                $sheet->getStyle("N{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'C00000'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FDE9D9'],
                    ],
                ]);
            } elseif ($sla === 'Đúng hạn') {
                $sheet->getStyle("N{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '228B22'],
                    ],
                ]);
            }

            // Highlight loại công việc
            $workType = $sheet->getCell("F{$row}")->getValue();
            if ($workType === 'Offline' || $workType === 'Online') {
                $sheet->getStyle("F{$row}")->getFont()->setBold(true);
            }
        }

        return [];
    }

    /**
     * Tính số dòng cần thiết cho nội dung wrap.
     *
     * Có hỗ trợ cả:
     * - Nội dung dài
     * - Xuống dòng \n
     */
    private function calculateLines(string $text, int $charsPerLine): int
    {
        if ($text === '') return 1;
        $lines = 0;
        foreach (explode("\n", $text) as $line) {
            $lines += max(1, (int) ceil(mb_strlen($line) / $charsPerLine));
        }
        return max(1, $lines);
    }
}