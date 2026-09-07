<?php

namespace App\Exports;

use App\Services\TechSystemReportService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TechSystemReportExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithStrictNullComparison
{
    protected Carbon $fromDate;
    protected Carbon $toDate;
    protected ?Carbon $fromDateCompleted;
    protected ?Carbon $toDateCompleted;
    protected array $techEmails;
    protected TechSystemReportService $service;

    public function __construct(
        string $fromDate,
        string $toDate,
        ?string $fromDateCompleted = null,
        ?string $toDateCompleted = null,
        array $techEmails = []
    ) {
        $this->fromDate = Carbon::parse($fromDate)->startOfDay();
        $this->toDate = Carbon::parse($toDate)->endOfDay();

        $this->fromDateCompleted = $fromDateCompleted
            ? Carbon::parse($fromDateCompleted)->startOfDay()
            : null;

        $this->toDateCompleted = $toDateCompleted
            ? Carbon::parse($toDateCompleted)->endOfDay()
            : null;

        $this->techEmails = array_values(
            array_map(
                fn ($email) => strtolower(trim($email)),
                array_filter($techEmails)
            )
        );

        $this->service = app(TechSystemReportService::class);
    }

    public function collection()
    {
        return $this->service
            ->getReport(
                $this->fromDate->toDateString(),
                $this->toDate->toDateString(),
                $this->fromDateCompleted?->toDateString(),
                $this->toDateCompleted?->toDateString(),
                $this->techEmails
            )
            ->map(fn ($item) => [

                /*
                |--------------------------------------------------------------------------
                | 1 - 7: Thông tin kỹ thuật viên
                |--------------------------------------------------------------------------
                */

                $item->technician_code,
                $item->technician_name,
                $item->technician_position,
                $item->store_count,
                $item->daily_target,
                $item->monthly_target,
                $item->total_completed,

                /*
                |--------------------------------------------------------------------------
                | 8 - 11: Phân loại công việc
                |--------------------------------------------------------------------------
                */

                $item->onsite_in_work_count,
                $item->onsite_off_work_count,
                $item->online_in_work_count,
                $item->online_off_work_count,

                /*
                |--------------------------------------------------------------------------
                | 12 - 14: Tỷ lệ + quy đổi
                |--------------------------------------------------------------------------
                */

                $item->completion_percent,
                $item->quy_doi_count,
                $item->completion_quy_doi_percent,

                /*
                |--------------------------------------------------------------------------
                | 15 - 26:
                |
                | Mỗi nhóm:
                | - Đúng hạn + CL
                | - Trễ
                | - Chưa đáp ứng
                |--------------------------------------------------------------------------
                */

                // Onsite trong giờ
                $item->onsite_in_work_on_time_quality_count,
                $item->onsite_in_work_late_count,
                $item->onsite_in_work_not_met_count,

                // Onsite ngoài giờ
                $item->onsite_off_work_on_time_quality_count,
                $item->onsite_off_work_late_count,
                $item->onsite_off_work_not_met_count,

                // Online trong giờ
                $item->online_in_work_on_time_quality_count,
                $item->online_in_work_late_count,
                $item->online_in_work_not_met_count,

                // Online ngoài giờ
                $item->online_off_work_on_time_quality_count,
                $item->online_off_work_late_count,
                $item->online_off_work_not_met_count,

                /*
                |--------------------------------------------------------------------------
                | 27 - 28: Tổng LATED
                |--------------------------------------------------------------------------
                */

                $item->total_late_count,
                $item->late_percent,

                /*
                |--------------------------------------------------------------------------
                | 29 - 34: Chất lượng
                |--------------------------------------------------------------------------
                */

                $item->onsite_quality_pass_count,
                $item->onsite_quality_fail_count,

                $item->online_in_work_quality_pass_count,
                $item->online_in_work_quality_fail_count,

                $item->online_off_work_quality_pass_count,
                $item->online_off_work_quality_fail_count,

                /*
                |--------------------------------------------------------------------------
                | 35 - 36: Tổng chất lượng
                |--------------------------------------------------------------------------
                */

                $item->quality_fail_count,
                $item->quality_fail_percent,
            ]);
    }

    public function headings(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | 1 - 7
            |--------------------------------------------------------------------------
            */

            'Mã NV',
            'Họ và tên',
            'Vị trí',
            'Số cửa hàng',
            'Định mức/ngày',
            'Định mức/tháng',
            'Tổng số vụ',

            /*
            |--------------------------------------------------------------------------
            | 8 - 11
            |--------------------------------------------------------------------------
            */

            'Onsite trong giờ',
            'Onsite ngoài giờ',
            'Online trong giờ',
            'Online ngoài giờ',

            /*
            |--------------------------------------------------------------------------
            | 12 - 14
            |--------------------------------------------------------------------------
            */

            'HT/ĐM (%)',
            'Số công việc quy đổi',
            'HT/ĐM quy đổi (%)',

            /*
            |--------------------------------------------------------------------------
            | 15 - 26
            |--------------------------------------------------------------------------
            */

            'Onsite trong giờ đạt TG + CL',
            'Onsite trong giờ trễ',
            'Onsite trong giờ chưa đáp ứng',

            'Onsite ngoài giờ đạt TG + CL',
            'Onsite ngoài giờ trễ',
            'Onsite ngoài giờ chưa đáp ứng',

            'Online trong giờ đạt TG + CL',
            'Online trong giờ trễ',
            'Online trong giờ chưa đáp ứng',

            'Online ngoài giờ đạt TG + CL',
            'Online ngoài giờ trễ',
            'Online ngoài giờ chưa đáp ứng',

            /*
            |--------------------------------------------------------------------------
            | 27 - 28
            |--------------------------------------------------------------------------
            */

            'Tổng không đạt TG',
            'Quá hạn (%)',

            /*
            |--------------------------------------------------------------------------
            | 29 - 34
            |--------------------------------------------------------------------------
            */

            'Onsite đạt CL',
            'Onsite không đạt CL',

            'Online trong giờ đạt CL',
            'Online trong giờ không đạt CL',

            'Online ngoài giờ đạt CL',
            'Online ngoài giờ không đạt CL',

            /*
            |--------------------------------------------------------------------------
            | 35 - 36
            |--------------------------------------------------------------------------
            */

            'Tổng không đạt CL',
            'Không đạt CL (%)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        /*
        |--------------------------------------------------------------------------
        | Freeze
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane('H2');

        /*
        |--------------------------------------------------------------------------
        | Auto filter
        |--------------------------------------------------------------------------
        */

        $sheet->setAutoFilter(
            "A1:{$highestColumn}{$highestRow}"
        );

        /*
        |--------------------------------------------------------------------------
        | Column width
        |--------------------------------------------------------------------------
        */

        $widths = [
            // 1 - 7
            'A' => 12,
            'B' => 22,
            'C' => 20,
            'D' => 12,
            'E' => 14,
            'F' => 15,
            'G' => 13,

            // 8 - 11
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,

            // 12 - 14
            'L' => 13,
            'M' => 16,
            'N' => 18,

            // 15 - 26
            'O' => 20,
            'P' => 18,
            'Q' => 20,

            'R' => 20,
            'S' => 18,
            'T' => 20,

            'U' => 20,
            'V' => 18,
            'W' => 20,

            'X' => 20,
            'Y' => 18,
            'Z' => 20,

            // 27 - 28
            'AA' => 15,
            'AB' => 13,

            // 29 - 34
            'AC' => 15,
            'AD' => 18,

            'AE' => 20,
            'AF' => 20,

            'AG' => 20,
            'AH' => 20,

            // 35 - 36
            'AI' => 15,
            'AJ' => 15,
        ];

        foreach ($widths as $col => $width) {
            $sheet
                ->getColumnDimension($col)
                ->setWidth($width);
        }

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle("A1:{$highestColumn}1")
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],

                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFD700',
                    ],
                ],

                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],

                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => [
                            'rgb' => 'D9D9D9',
                        ],
                    ],
                ],
            ]);

        $sheet
            ->getRowDimension(1)
            ->setRowHeight(65);

        if ($highestRow >= 2) {

            /*
            |--------------------------------------------------------------------------
            | Text
            |--------------------------------------------------------------------------
            */

            $sheet
                ->getStyle("A2:C{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER);

            /*
            |--------------------------------------------------------------------------
            | Numeric
            |--------------------------------------------------------------------------
            */

            $sheet
                ->getStyle("D2:AJ{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            /*
            |--------------------------------------------------------------------------
            | Percentage
            |--------------------------------------------------------------------------
            |
            | percent() đang trả về dạng:
            |
            | 116.67
            |
            | chứ không phải:
            |
            | 1.1667
            |
            | Vì vậy KHÔNG dùng format Excel 0.00%.
            |
            */

            foreach (['L', 'N', 'AB', 'AJ'] as $col) {
                $sheet
                    ->getStyle("{$col}2:{$col}{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('0.00');
            }

            /*
            |--------------------------------------------------------------------------
            | Integer
            |--------------------------------------------------------------------------
            */

            foreach ([
                'D',
                'E',
                'F',
                'G',

                'H',
                'I',
                'J',
                'K',

                'M',

                'O',
                'P',
                'Q',

                'R',
                'S',
                'T',

                'U',
                'V',
                'W',

                'X',
                'Y',
                'Z',

                'AA',

                'AC',
                'AD',
                'AE',
                'AF',
                'AG',
                'AH',

                'AI',
            ] as $col) {
                $sheet
                    ->getStyle("{$col}2:{$col}{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('0');
            }

            /*
            |--------------------------------------------------------------------------
            | Default row height
            |--------------------------------------------------------------------------
            */

            $sheet
                ->getDefaultRowDimension()
                ->setRowHeight(20);
        }

        /*
        |--------------------------------------------------------------------------
        | Border
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle("A1:{$highestColumn}{$highestRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => [
                            'rgb' => 'D9D9D9',
                        ],
                    ],
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Wrap
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle("A1:{$highestColumn}{$highestRow}")
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_CENTER);

        return [];
    }
}